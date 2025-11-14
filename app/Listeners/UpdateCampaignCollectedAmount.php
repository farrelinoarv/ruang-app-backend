<?php

namespace App\Listeners;

use App\Events\DonationPaid;
use App\Services\CampaignService;
use Illuminate\Contracts\Queue\ShouldQueue;

class UpdateCampaignCollectedAmount implements ShouldQueue
{
    public function __construct(
        protected CampaignService $campaignService
    ) {
    }

    /**
     * Handle the event.
     */
    public function handle(DonationPaid $event): void
    {
        $donation = $event->donation;
        $campaign = $donation->campaign;

        if ($donation->payment_status === 'success') {
            $this->campaignService->updateCollectedAmount($campaign, (float) $donation->amount);
        }
    }
}
