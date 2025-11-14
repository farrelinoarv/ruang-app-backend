<?php

namespace App\Listeners;

use App\Events\DonationPaid;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendDonationNotification implements ShouldQueue
{
    public function __construct(
        protected NotificationService $notificationService
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
            // Notify campaign owner
            $this->notificationService->sendToCampaignOwner(
                $campaign,
                'Donasi Baru Diterima!',
                sprintf(
                    '%s telah mendonasikan Rp %s untuk campaign "%s"',
                    $donation->donor_name,
                    number_format((float) $donation->amount, 0, ',', '.'),
                    $campaign->title
                ),
                [
                    'donation_id' => $donation->id,
                    'campaign_id' => $campaign->id,
                    'amount' => $donation->amount,
                ],
                'donation_received'
            );

            // Notify donor if registered user
            if ($donation->user) {
                $this->notificationService->sendToUser(
                    $donation->user,
                    'Donasi Berhasil!',
                    sprintf(
                        'Terima kasih telah mendonasikan Rp %s untuk campaign "%s"',
                        number_format((float) $donation->amount, 0, ',', '.'),
                        $campaign->title
                    ),
                    [
                        'donation_id' => $donation->id,
                        'campaign_id' => $campaign->id,
                        'amount' => $donation->amount,
                    ],
                    'donation_success'
                );
            }
        }
    }
}
