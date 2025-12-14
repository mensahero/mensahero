<?php

namespace App\Events\Team;

use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserRemovedEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public readonly array $team, public readonly User $user) {}

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
        return 'user.removed';
    }

    public function broadcastWith(): array
    {
        return [
            'team' => [
                'id'           => $this->team['id'],
                'label'        => $this->team['name'],
                'current_team' => false,
                'default'      => $this->team['default'],
            ],
            'user' => $this->user->only('id', 'name'),
        ];
    }
}
