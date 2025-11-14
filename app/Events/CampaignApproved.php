<?php

namespace App\Events;

use App\Models\Campaign;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CampaignApproved
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Campaign $campaign;
    public User $admin;

    /**
     * Create a new event instance.
     */
    public function __construct(Campaign $campaign, User $admin)
    {
        $this->campaign = $campaign;
        $this->admin = $admin;
    }
}
