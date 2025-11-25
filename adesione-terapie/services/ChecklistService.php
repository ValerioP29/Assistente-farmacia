<?php

namespace Modules\AdesioneTerapie\Services;

use Modules\AdesioneTerapie\Repositories\CheckRepository;

class ChecklistService
{
    private CheckRepository $checkRepository;
    private QuestionnaireService $questionnaireService;
    private array $checkCols;

    public function __construct(CheckRepository $checkRepository, QuestionnaireService $questionnaireService, array $checkCols)
    {
        $this->checkRepository = $checkRepository;
        $this->questionnaireService = $questionnaireService;
        $this->checkCols = $checkCols;
    }

    public function normalizeChecklistQuestions($questions): array
    {
        return $this->questionnaireService->normalizeChecklistQuestions($questions);
    }

    public function findChecklistId(int $therapyId): ?int
    {
        if (!$this->checkCols['therapy']) {
            return null;
        }

        $rows = $this->checkRepository->listByTherapyOrdered($therapyId);
        foreach ($rows as $row) {
            $notes = $this->checkCols['notes'] ? ($row[$this->checkCols['notes']] ?? '') : '';
            $decoded = json_decode($notes, true);
            if (is_array($decoded) && (!empty($decoded['questions']) || ($decoded['type'] ?? '') === 'checklist')) {
                return (int)($row[$this->checkCols['id']] ?? 0);
            }
        }

        return null;
    }
}
