<?php

namespace App\Listeners;

use App\Events\UpdatePosted;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;

class NotifyDonorsOfUpdate implements ShouldQueue
{
    public function __construct(
        protected NotificationService $notificationService
    ) {
    }

    /**
     * Handle the event.
     */
    public function handle(UpdatePosted $event): void
    {
        $update = $event->update;
        $campaign = $update->campaign;

        // Notify all donors of this campaign
        $this->notificationService->sendToDonors(
            $campaign,
            'Update Campaign Baru',
            sprintf(
                'Campaign "%s" yang Anda dukung baru saja memposting update: "%s"',
                $campaign->title,
                $update->title
            ),
            [
                'campaign_id' => $campaign->id,
                'update_id' => $update->id,
                'campaign_slug' => $campaign->slug,
            ],
            'campaign_update'
        );
    }
}
