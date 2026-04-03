<?php

namespace App\Events;

use App\Models\SafetyAlert;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AlertFired implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public SafetyAlert $alert) {}

    public function broadcastAs(): string
    {
        return 'alert.fired';
    }

    /**
     * @return array<int, PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('compass.'.$this->alert->teacher_id),
            new PrivateChannel('alerts.'.$this->alert->district_id),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        $this->alert->loadMissing(['student', 'session.space']);

        return [
            'alert_id' => $this->alert->id,
            'session_id' => $this->alert->session_id,
            'student_id' => $this->alert->student_id,
            'student_name' => $this->alert->student->name,
            'severity' => $this->alert->severity,
            'category' => $this->alert->category,
            'timestamp' => $this->alert->created_at->toISOString(),
            'space_title' => $this->alert->session?->space?->title ?? '',
        ];
    }
}
