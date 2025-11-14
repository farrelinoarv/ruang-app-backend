<?php

namespace App\Events;

use App\Models\Campaign;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CampaignRejected
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Campaign $campaign;
    public User $admin;
    public string $reason;

    /**
     * Create a new event instance.
     */
    public function __construct(Campaign $campaign, User $admin, string $reason)
    {
        $this->campaign = $campaign;
        $this->admin = $admin;
        $this->reason = $reason;
    }
}
