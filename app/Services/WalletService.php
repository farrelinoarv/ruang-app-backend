<?php

namespace App\Services;

use App\Models\Campaign;
use App\Models\Donation;
use App\Models\MasterAccount;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WithdrawalRequest;
use Illuminate\Support\Facades\DB;

class WalletService
{
    /**
     * Add funds to a user's wallet.
     */
    public function addFunds(User $user, float $amount, string $source = 'general'): bool
    {
        try {
            DB::beginTransaction();

            $wallet = $user->wallet;
            if (!$wallet) {
                $wallet = Wallet::create([
                    'user_id' => $user->id,
                    'balance' => 0,
                    'total_income' => 0,
                    'total_withdrawn' => 0,
                ]);
            }

            $wallet->increment('balance', $amount);
            $wallet->increment('total_income', $amount);

            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Failed to add funds to wallet: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Deduct funds from a user's wallet.
     */
    public function deductFunds(User $user, float $amount, string $reason = 'general'): bool
    {
        try {
            DB::beginTransaction();

            $wallet = $user->wallet;
            if (!$wallet || $wallet->balance < $amount) {
                DB::rollBack();
                return false;
            }

            $wallet->decrement('balance', $amount);
            $wallet->increment('total_withdrawn', $amount);

            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Failed to deduct funds from wallet: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Transfer donation amount to master account.
     */
    public function transferToMasterAccount(Donation $donation): bool
    {
        try {
            DB::beginTransaction();

            $masterAccount = MasterAccount::getInstance();
            $masterAccount->addFunds((float) $donation->amount);

            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Failed to transfer to master account: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Transfer withdrawal amount from master account to user wallet.
     */
    public function transferFromMasterAccount(WithdrawalRequest $withdrawal): bool
    {
        try {
            DB::beginTransaction();

            $masterAccount = MasterAccount::getInstance();

            // Check if master account has enough balance
            if (!$masterAccount->deductFunds((float) $withdrawal->requested_amount)) {
                DB::rollBack();
                return false;
            }

            // Add funds to user's wallet
            $this->addFunds($withdrawal->user, (float) $withdrawal->requested_amount, 'withdrawal');

            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Failed to transfer from master account: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get user's wallet balance.
     */
    public function getBalance(User $user): float
    {
        $wallet = $user->wallet;
        return $wallet ? (float) $wallet->balance : 0.0;
    }

    /**
     * Get transaction history for a user.
     */
    public function getTransactionHistory(User $user, int $limit = 50)
    {
        return [
            'donations' => $user->donations()
                ->with('campaign:id,title')
                ->latest()
                ->limit($limit)
                ->get(),
            'withdrawals' => $user->withdrawalRequests()
                ->with('campaign:id,title')
                ->latest()
                ->limit($limit)
                ->get(),
        ];
    }

    /**
     * Create wallet for user if doesn't exist.
     */
    public function ensureWalletExists(User $user): Wallet
    {
        return Wallet::firstOrCreate(
            ['user_id' => $user->id],
            [
                'balance' => 0,
                'total_income' => 0,
                'total_withdrawn' => 0,
            ]
        );
    }

    /**
     * Get master account balance.
     */
    public function getMasterAccountBalance(): float
    {
        $masterAccount = MasterAccount::getInstance();
        return (float) $masterAccount->balance;
    }
}
