<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

/** An in-app alert in the office: a new enquiry, an online booking, stock to count. */
class ClinicAlert extends Notification
{
    public function __construct(
        public readonly string $event,
        public readonly string $title,
        public readonly string $body,
        public readonly ?string $url = null,
    ) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return ['event' => $this->event, 'title' => $this->title, 'body' => $this->body, 'url' => $this->url];
    }
}
