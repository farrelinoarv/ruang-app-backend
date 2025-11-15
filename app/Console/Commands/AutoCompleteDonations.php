<?php

namespace App\Console\Commands;

use App\Events\DonationPaid;
use App\Models\Donation;
use Illuminate\Console\Command;

class AutoCompleteDonations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'donations:auto-complete';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Auto-complete pending donations (DEV ONLY - simulates successful payment)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $pendingDonations = Donation::where('payment_status', 'pending')->get();

        if ($pendingDonations->isEmpty()) {
            $this->info('No pending donations to process.');
            return 0;
        }

        $this->info("Found {$pendingDonations->count()} pending donation(s)...");

        foreach ($pendingDonations as $donation) {
            // Update donation to success
            $donation->update([
                'payment_status' => 'success',
                'payment_method' => 'gopay',
                'midtrans_transaction_id' => 'auto-' . uniqid(),
                'transaction_ref' => 'DEV-AUTO-' . time(),
            ]);

            // Fire DonationPaid event (updates campaign & wallet)
            event(new DonationPaid($donation));

            $this->info("✅ Donation #{$donation->id} ({$donation->midtrans_order_id}) marked as SUCCESS");
        }

        $this->info("\n🎉 Completed {$pendingDonations->count()} donation(s)!");

        return 0;
    }
}
