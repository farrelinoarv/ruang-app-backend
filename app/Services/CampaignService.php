<?php

namespace App\Services;

use App\Events\CampaignApproved;
use App\Events\CampaignRejected;
use App\Models\Campaign;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CampaignService
{
    public function __construct(
        protected WalletService $walletService
    ) {}

    /**
     * Approve a campaign.
     */
    public function approveCampaign(Campaign $campaign, User $admin, string $notes = null): bool
    {
        try {
            DB::beginTransaction();

            // Update campaign status
            $campaign->update(['status' => 'approved']);

            // Update verification request
            if ($campaign->verificationRequest) {
                $campaign->verificationRequest->update([
                    'verification_status' => 'approved',
                    'reviewed_by' => $admin->id,
                    'reviewed_at' => now(),
                    'notes' => $notes,
                ]);
            }

            // Verify civitas user if this is their first approved campaign
            $firstCampaign = $campaign->user->campaigns()
                ->where('status', 'approved')
                ->count() === 1;

            if ($firstCampaign) {
                $campaign->user->update(['is_verified_civitas' => true]);

                // Ensure wallet exists for verified civitas
                $this->walletService->ensureWalletExists($campaign->user);
            }

            DB::commit();

            // Fire event
            event(new CampaignApproved($campaign, $admin));

            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Failed to approve campaign: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Reject a campaign.
     */
    public function rejectCampaign(Campaign $campaign, User $admin, string $reason): bool
    {
        try {
            DB::beginTransaction();

            // Update campaign status
            $campaign->update(['status' => 'rejected']);

            // Update verification request
            if ($campaign->verificationRequest) {
                $campaign->verificationRequest->update([
                    'verification_status' => 'rejected',
                    'reviewed_by' => $admin->id,
                    'reviewed_at' => now(),
                    'notes' => $reason,
                ]);
            }

            DB::commit();

            // Fire event
            event(new CampaignRejected($campaign, $admin, $reason));

            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Failed to reject campaign: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Close expired campaigns.
     */
    public function closeExpiredCampaigns(): int
    {
        $expiredCampaigns = Campaign::where('status', 'approved')
            ->where('deadline', '<', now())
            ->get();

        $count = 0;
        foreach ($expiredCampaigns as $campaign) {
            $campaign->update(['status' => 'closed']);
            $count++;
        }

        return $count;
    }

    /**
     * Update campaign collected amount.
     */
    public function updateCollectedAmount(Campaign $campaign, float $amount): bool
    {
        try {
            $campaign->increment('collected_amount', $amount);
            return true;
        } catch (\Exception $e) {
            \Log::error('Failed to update collected amount: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if campaign target is reached.
     */
    public function isTargetReached(Campaign $campaign): bool
    {
        return $campaign->collected_amount >= $campaign->target_amount;
    }

    /**
     * Get campaign progress percentage.
     */
    public function getProgressPercentage(Campaign $campaign): float
    {
        if ($campaign->target_amount == 0) {
            return 0;
        }
        return min(100, ($campaign->collected_amount / $campaign->target_amount) * 100);
    }

    /**
     * Get active campaigns.
     */
    public function getActiveCampaigns()
    {
        return Campaign::where('status', 'approved')
            ->where('deadline', '>=', now())
            ->with(['user', 'category'])
            ->latest()
            ->get();
    }

    /**
     * Get pending campaigns for review.
     */
    public function getPendingCampaigns()
    {
        return Campaign::where('status', 'pending')
            ->with(['user', 'category', 'verificationRequest'])
            ->latest()
            ->get();
    }
}
