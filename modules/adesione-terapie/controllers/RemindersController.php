<?php

namespace Modules\AdesioneTerapie\Controllers;

use Modules\AdesioneTerapie\Repositories\ReminderRepository;
use Modules\AdesioneTerapie\Services\FormattingService;
use RuntimeException;

class RemindersController
{
    private ReminderRepository $reminderRepository;
    private FormattingService $formattingService;
    private int $pharmacyId;
    private array $reminderCols;
    private array $therapyCols;
    private $cleanCallback;
    private $nowCallback;
    private $verifyTherapyOwnershipCallback;

    public function __construct(
        ReminderRepository $reminderRepository,
        FormattingService $formattingService,
        int $pharmacyId,
        array $reminderCols,
        array $therapyCols,
        callable $cleanCallback,
        callable $nowCallback,
        callable $verifyTherapyOwnershipCallback
    ) {
        $this->reminderRepository = $reminderRepository;
        $this->formattingService = $formattingService;
        $this->pharmacyId = $pharmacyId;
        $this->reminderCols = $reminderCols;
        $this->therapyCols = $therapyCols;
        $this->cleanCallback = $cleanCallback;
        $this->nowCallback = $nowCallback;
        $this->verifyTherapyOwnershipCallback = $verifyTherapyOwnershipCallback;
    }

    public function saveReminder(array $payload): array
    {
        $reminderId = isset($payload['reminder_id']) && $payload['reminder_id'] !== '' ? (int)$payload['reminder_id'] : null;
        $therapyId = isset($payload['therapy_id']) && $payload['therapy_id'] !== '' ? (int)$payload['therapy_id'] : 0;

        if (!$therapyId) {
            throw new RuntimeException('Seleziona una terapia per il promemoria.');
        }

        $this->verifyTherapyOwnership($therapyId);

        $data = [];
        if ($this->reminderCols['therapy']) {
            $data[$this->reminderCols['therapy']] = $therapyId;
        }

        if ($this->reminderCols['title']) {
            $title = $this->clean($payload['title'] ?? '');
            if ($title === '' && !empty($payload['message'])) {
                $title = mb_substr($this->clean($payload['message']), 0, 120);
            }
            $data[$this->reminderCols['title']] = $title !== '' ? $title : 'Promemoria terapia';
        }

        if ($this->reminderCols['scheduled_at'] && !empty($payload['scheduled_at'])) {
            $data[$this->reminderCols['scheduled_at']] = str_replace('T', ' ', $payload['scheduled_at']);
        }

        if ($this->reminderCols['scheduled_at'] && empty($data[$this->reminderCols['scheduled_at']])) {
            throw new RuntimeException('Imposta data e ora del promemoria.');
        }

        $allowedChannels = ['sms', 'email', 'push'];
        $channel = strtolower($payload['channel'] ?? 'email');
        if (!in_array($channel, $allowedChannels, true)) {
            $channel = 'email';
        }

        $messageText = $this->clean($payload['message'] ?? '');
        if ($messageText === '') {
            throw new RuntimeException('Inserisci il messaggio del promemoria.');
        }

        $messagePayload = [
            'text' => $messageText,
            'type' => $this->clean($payload['type'] ?? 'one-shot'),
            'recurrence' => $this->clean($payload['recurrence_rule'] ?? ''),
        ];

        if ($this->reminderCols['message']) {
            $data[$this->reminderCols['message']] = json_encode($messagePayload, JSON_UNESCAPED_UNICODE);
        }

        if ($this->reminderCols['channel']) {
            $data[$this->reminderCols['channel']] = $channel;
        }

        if ($this->reminderCols['status']) {
            $status = $this->clean($payload['status'] ?? 'scheduled');
            $data[$this->reminderCols['status']] = $status ?: 'scheduled';
        }

        if ($this->reminderCols['updated_at']) {
            $data[$this->reminderCols['updated_at']] = $this->now();
        }

        $filtered = $this->reminderRepository->filterData($data);

        if (
            !$reminderId &&
            $this->reminderCols['therapy'] &&
            $this->reminderCols['scheduled_at'] &&
            !empty($filtered[$this->reminderCols['scheduled_at']])
        ) {
            $existingReminderId = $this->reminderRepository->findExistingReminder(
                $therapyId,
                $filtered[$this->reminderCols['scheduled_at']],
                $this->reminderCols['title'] && isset($filtered[$this->reminderCols['title']])
                    ? $filtered[$this->reminderCols['title']]
                    : null,
                $this->reminderCols
            );

            if ($existingReminderId) {
                $reminderId = $existingReminderId;
            }
        }

        if ($reminderId) {
            $this->reminderRepository->update($reminderId, $filtered, $this->reminderCols['id']);
        } else {
            if ($this->reminderCols['created_at'] && !isset($filtered[$this->reminderCols['created_at']])) {
                $filtered[$this->reminderCols['created_at']] = $this->now();
            }
            $reminderId = $this->reminderRepository->insert($filtered);
        }

        return $this->findReminder($reminderId);
    }

    public function findReminder(int $reminderId): array
    {
        $reminder = $this->reminderRepository->find(
            $reminderId,
            $this->reminderCols,
            $this->therapyCols,
            $this->therapyCols['pharmacy'] ? $this->pharmacyId : null
        );

        if (!$reminder) {
            throw new RuntimeException('Promemoria non trovato.');
        }

        return $this->formattingService->formatReminder($reminder, $this->reminderCols);
    }

    public function listReminders(?int $therapyId = null): array
    {
        $rows = $this->reminderRepository->list(
            $therapyId,
            $this->reminderCols,
            $this->therapyCols,
            $this->therapyCols['pharmacy'] ? $this->pharmacyId : null
        );

        $reminders = [];
        foreach ($rows as $row) {
            $reminders[] = $this->formattingService->formatReminder($row, $this->reminderCols);
        }

        return $reminders;
    }

    private function clean(?string $value): string
    {
        return call_user_func($this->cleanCallback, $value);
    }

    private function now(): string
    {
        return call_user_func($this->nowCallback);
    }

    private function verifyTherapyOwnership(int $therapyId): void
    {
        call_user_func($this->verifyTherapyOwnershipCallback, $therapyId);
    }
}
