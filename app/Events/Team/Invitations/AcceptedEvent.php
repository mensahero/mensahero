<?php

namespace App\Events\Team\Invitations;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AcceptedEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public readonly object $team) {}

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("Team.{$this->team['id']}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'invitation.accepted';
    }

    public function broadcastWith(): array
    {
        return [
            'team' => [
                'id'   => $this->team['id'],
                'name' => $this->team['name'],
            ],
        ];
    }
}
