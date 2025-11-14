<?php

namespace App\Events;

use App\Models\User;
use App\Models\WithdrawalRequest;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WithdrawalRejected
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public WithdrawalRequest $withdrawal;
    public User $admin;
    public string $reason;

    /**
     * Create a new event instance.
     */
    public function __construct(WithdrawalRequest $withdrawal, User $admin, string $reason)
    {
        $this->withdrawal = $withdrawal;
        $this->admin = $admin;
        $this->reason = $reason;
    }
}
