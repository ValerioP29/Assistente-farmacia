<?php

namespace Modules\AdesioneTerapie\Services;

class ReminderService
{
    private $notificationService;

    public function __construct($notificationService = null)
    {
        $this->notificationService = $notificationService;
    }

    public function notifyNewReminder(array $reminder): void
    {
        if (!$this->notificationService) {
            return;
        }

        $payload = [
            'title' => 'Nuovo promemoria terapia',
            'message' => $reminder['message'] ?? ($reminder['title'] ?? ''),
            'therapy_id' => $reminder['therapy_id'] ?? null,
        ];

        if (method_exists($this->notificationService, 'create')) {
            $this->notificationService->create($payload);
            return;
        }

        if (method_exists($this->notificationService, 'createNotification')) {
            $this->notificationService->createNotification($payload);
        }
    }
}
