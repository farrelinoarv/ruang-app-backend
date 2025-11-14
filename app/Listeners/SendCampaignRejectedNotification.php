<?php

namespace App\Listeners;

use App\Events\CampaignRejected;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendCampaignRejectedNotification implements ShouldQueue
{
    public function __construct(
        protected NotificationService $notificationService
    ) {
    }

    /**
     * Handle the event.
     */
    public function handle(CampaignRejected $event): void
    {
        $campaign = $event->campaign;
        $user = $campaign->user;

        $this->notificationService->sendToUser(
            $user,
            'Campaign Ditolak',
            sprintf(
                'Maaf, campaign Anda "%s" ditolak oleh admin. Alasan: %s',
                $campaign->title,
                $event->reason
            ),
            [
                'campaign_id' => $campaign->id,
                'reason' => $event->reason,
            ],
            'campaign_rejected'
        );
    }
}
