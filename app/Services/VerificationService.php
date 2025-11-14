<?php

namespace App\Services;

use App\Models\CampaignVerificationRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class VerificationService
{
    /**
     * Approve a verification request.
     */
    public function approveVerification(CampaignVerificationRequest $request, User $admin, string $notes = null): bool
    {
        try {
            DB::beginTransaction();

            $request->update([
                'verification_status' => 'approved',
                'reviewed_by' => $admin->id,
                'reviewed_at' => now(),
                'notes' => $notes,
            ]);

            // This will be handled by CampaignService::approveCampaign
            // Just update the verification request here

            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Failed to approve verification: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Reject a verification request.
     */
    public function rejectVerification(CampaignVerificationRequest $request, User $admin, string $reason): bool
    {
        try {
            DB::beginTransaction();

            $request->update([
                'verification_status' => 'rejected',
                'reviewed_by' => $admin->id,
                'reviewed_at' => now(),
                'notes' => $reason,
            ]);

            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Failed to reject verification: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Verify a civitas user.
     */
    public function verifyCivitas(User $user): bool
    {
        try {
            $user->update(['is_verified_civitas' => true]);
            return true;
        } catch (\Exception $e) {
            \Log::error('Failed to verify civitas: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if user is verified civitas.
     */
    public function isVerifiedCivitas(User $user): bool
    {
        return $user->is_verified_civitas;
    }

    /**
     * Get pending verification requests.
     */
    public function getPendingVerifications()
    {
        return CampaignVerificationRequest::where('verification_status', 'pending')
            ->with(['campaign.user', 'campaign.category'])
            ->latest()
            ->get();
    }
}
