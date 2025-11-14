<?php

namespace App\Events;

use App\Models\Update;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UpdatePosted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Update $update;

    /**
     * Create a new event instance.
     */
    public function __construct(Update $update)
    {
        $this->update = $update;
    }
}
