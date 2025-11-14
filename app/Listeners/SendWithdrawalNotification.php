<?php

namespace App\Listeners;

use App\Events\WithdrawalApproved;
use App\Events\WithdrawalRejected;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendWithdrawalNotification implements ShouldQueue
{
    public function __construct(
        protected NotificationService $notificationService
    ) {
    }

    /**
     * Handle the event.
     */
    public function handle(WithdrawalApproved|WithdrawalRejected $event): void
    {
        $withdrawal = $event->withdrawal;
        $user = $withdrawal->user;

        if ($event instanceof WithdrawalApproved) {
            $this->notificationService->sendToUser(
                $user,
                'Pencairan Dana Disetujui',
                sprintf(
                    'Permintaan pencairan dana sebesar Rp %s untuk campaign "%s" telah disetujui. Dana akan segera ditransfer ke rekening Anda.',
                    number_format((float) $withdrawal->requested_amount, 0, ',', '.'),
                    $withdrawal->campaign->title
                ),
                [
                    'withdrawal_id' => $withdrawal->id,
                    'campaign_id' => $withdrawal->campaign_id,
                    'amount' => $withdrawal->requested_amount,
                ],
                'withdrawal_approved'
            );
        } else {
            $this->notificationService->sendToUser(
                $user,
                'Pencairan Dana Ditolak',
                sprintf(
                    'Permintaan pencairan dana sebesar Rp %s untuk campaign "%s" ditolak. Alasan: %s',
                    number_format((float) $withdrawal->requested_amount, 0, ',', '.'),
                    $withdrawal->campaign->title,
                    $event->reason
                ),
                [
                    'withdrawal_id' => $withdrawal->id,
                    'campaign_id' => $withdrawal->campaign_id,
                    'reason' => $event->reason,
                ],
                'withdrawal_rejected'
            );
        }
    }
}
