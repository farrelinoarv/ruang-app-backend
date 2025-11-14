<?php

namespace App\Listeners;

use App\Events\WithdrawalApproved;
use App\Services\WalletService;
use Illuminate\Contracts\Queue\ShouldQueue;

class UpdateWalletBalance implements ShouldQueue
{
    public function __construct(
        protected WalletService $walletService
    ) {
    }

    /**
     * Handle the event.
     */
    public function handle(WithdrawalApproved $event): void
    {
        $withdrawal = $event->withdrawal;

        // Transfer funds from master account to user wallet
        $this->walletService->transferFromMasterAccount($withdrawal);
    }
}
