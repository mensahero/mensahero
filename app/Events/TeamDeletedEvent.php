<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Broadcasting\ShouldRescue;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TeamDeletedEvent implements ShouldBroadcast, ShouldRescue
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public readonly array $team) {}

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

    public function broadcastWith(): array
    {
        return [
            'id'           => $this->team['id'],
            'label'        => $this->team['name'],
            'current_team' => false,
            'default'      => $this->team['default'],
        ];
    }

    public function broadcastAs(): string
    {
        return 'deleted';
    }
}
