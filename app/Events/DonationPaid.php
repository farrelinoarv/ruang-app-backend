<?php

namespace App\Events;

use App\Models\Donation;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DonationPaid
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Donation $donation;

    /**
     * Create a new event instance.
     */
    public function __construct(Donation $donation)
    {
        $this->donation = $donation;
    }
}
