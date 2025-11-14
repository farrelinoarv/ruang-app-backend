<?php

namespace App\Events;

use App\Models\User;
use App\Models\WithdrawalRequest;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WithdrawalApproved
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public WithdrawalRequest $withdrawal;
    public User $admin;

    /**
     * Create a new event instance.
     */
    public function __construct(WithdrawalRequest $withdrawal, User $admin)
    {
        $this->withdrawal = $withdrawal;
        $this->admin = $admin;
    }
}
