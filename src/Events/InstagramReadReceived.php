<?php

namespace ScriptDevelop\InstagramApiManager\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

class InstagramReadReceived implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets;

    public array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function broadcastOn(): Channel
    {
        $channelName = 'instagram-messages';

        return config('instagram.broadcast.channel_type') === 'private'
            ? new PrivateChannel($channelName)
            : new Channel($channelName);
    }

    public function broadcastAs(): string
    {
        return 'InstagramReadReceived';
    }
}
