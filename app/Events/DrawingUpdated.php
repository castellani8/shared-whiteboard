<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DrawingUpdated implements ShouldBroadcast
{
    use Dispatchable, SerializesModels, InteractsWithSockets;

    public $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function broadcastOn()
    {
        return ['whiteboard'];
    }


    public function broadcastAs()
    {
        return 'drawing.updated';
    }
}

