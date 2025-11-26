<?php

namespace Modules\AdesioneTerapie\Services;

class TimelineService
{
    public function buildTimeline(array $checks, array $reminders): array
    {
        $timeline = [];

        foreach ($checks as $check) {
            if (($check['type'] ?? 'execution') === 'checklist') {
                continue;
            }

            $answersPreview = '';
            if (!empty($check['answers'])) {
                $first = $check['answers'][0];
                $previewParts = [];
                if (!empty($first['question'])) {
                    $previewParts[] = trim((string)$first['question']);
                }
                $valueLabel = $first['value_label'] ?? ($first['value'] === 'yes' ? 'Sì' : ($first['value'] === 'no' ? 'No' : ''));
                if (!empty($valueLabel)) {
                    $previewParts[] = trim((string)$valueLabel);
                }
                if (!empty($first['note'])) {
                    $previewParts[] = trim((string)$first['note']);
                }
                $answersPreview = trim(implode(': ', $previewParts));
                if (function_exists('mb_substr')) {
                    $answersPreview = mb_substr($answersPreview, 0, 60, 'UTF-8');
                } else {
                    $answersPreview = substr($answersPreview, 0, 60);
                }
            }

            $timeline[] = [
                'type' => 'check',
                'title' => 'Visita di controllo',
                'scheduled_at' => $check['scheduled_at'] ?? null,
                'details' => $answersPreview !== '' ? $answersPreview : ($check['assessment'] ?? ''),
                'has_answers' => !empty($check['answers']),
                'answers_preview' => $answersPreview,
                'therapy_id' => $check['therapy_id'] ?? null,
            ];
        }

        foreach ($reminders as $reminder) {
            $timeline[] = [
                'type' => 'reminder',
                'title' => $reminder['title'] ?? 'Promemoria terapia',
                'scheduled_at' => $reminder['scheduled_at'] ?? null,
                'details' => $reminder['message'] ?? '',
                'therapy_id' => $reminder['therapy_id'] ?? null,
            ];
        }

        usort($timeline, static function ($a, $b) {
            return strtotime($a['scheduled_at'] ?? '1970-01-01') <=> strtotime($b['scheduled_at'] ?? '1970-01-01');
        });

        return $timeline;
    }

    public function getLastCheck(array $checks): ?array
    {
        if (empty($checks)) {
            return null;
        }

        $executions = array_filter($checks, static function ($check) {
            return ($check['type'] ?? 'execution') !== 'checklist';
        });

        if (empty($executions)) {
            return null;
        }

        usort($executions, static function ($a, $b) {
            return strtotime($b['scheduled_at'] ?? '1970-01-01') <=> strtotime($a['scheduled_at'] ?? '1970-01-01');
        });

        return $executions[0];
    }

    public function getUpcomingReminder(array $reminders): ?array
    {
        $upcoming = array_filter($reminders, static function ($reminder) {
            return isset($reminder['scheduled_at']) && strtotime($reminder['scheduled_at']) >= time();
        });

        if (empty($upcoming)) {
            return null;
        }

        usort($upcoming, static function ($a, $b) {
            return strtotime($a['scheduled_at']) <=> strtotime($b['scheduled_at']);
        });

        return $upcoming[0];
    }
}
