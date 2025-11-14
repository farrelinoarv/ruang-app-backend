<?php

namespace App\Listeners;

use App\Events\DonationPaid;
use App\Services\WalletService;
use Illuminate\Contracts\Queue\ShouldQueue;

class UpdateMasterAccountBalance implements ShouldQueue
{
    public function __construct(
        protected WalletService $walletService
    ) {
    }

    /**
     * Handle the event.
     */
    public function handle(DonationPaid $event): void
    {
        $donation = $event->donation;

        if ($donation->payment_status === 'success') {
            $this->walletService->transferToMasterAccount($donation);
        }
    }
}
