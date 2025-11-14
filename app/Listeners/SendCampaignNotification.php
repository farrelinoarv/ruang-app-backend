<?php

namespace App\Listeners;

use App\Events\CampaignApproved;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendCampaignNotification implements ShouldQueue
{
    public function __construct(
        protected NotificationService $notificationService
    ) {
    }

    /**
     * Handle the event.
     */
    public function handle(CampaignApproved $event): void
    {
        $campaign = $event->campaign;
        $user = $campaign->user;

        $this->notificationService->sendToUser(
            $user,
            'Campaign Disetujui!',
            sprintf(
                'Selamat! Campaign Anda "%s" telah disetujui dan sekarang aktif. Mulai galang dana sekarang!',
                $campaign->title
            ),
            [
                'campaign_id' => $campaign->id,
                'campaign_slug' => $campaign->slug,
            ],
            'campaign_approved'
        );
    }
}
