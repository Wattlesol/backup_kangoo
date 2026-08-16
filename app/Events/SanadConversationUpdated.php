<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SanadConversationUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $bookingId;
    public string $type;
    public array $payload;

    public function __construct(int $bookingId, string $type, array $payload = [])
    {
        $this->bookingId = $bookingId;
        $this->type = $type;
        $this->payload = $payload;
    }

    public function broadcastOn()
    {
        return new PrivateChannel('sanad.request.' . $this->bookingId);
    }

    public function broadcastAs(): string
    {
        return 'sanad.conversation.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'booking_id' => $this->bookingId,
            'type' => $this->type,
            'payload' => $this->payload,
            'emitted_at' => now()->toIso8601String(),
        ];
    }
}
