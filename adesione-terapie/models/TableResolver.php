<?php
/**
 * Utility per risolvere tabelle e colonne dinamicamente
 */

class AdesioneTableResolver
{
    private static array $tableCache = [];
    private static array $columnsCache = [];

    private static array $tableMap = [
        'patient' => 'jta_patients',
        'patients' => 'jta_patients',
        'therapy' => 'jta_therapies',
        'therapies' => 'jta_therapies',
        'assistant' => 'jta_therapy_assistant',
        'assistants' => 'jta_therapy_assistant',
        'consent' => 'jta_therapy_consents',
        'consents' => 'jta_therapy_consents',
        'consensi' => 'jta_therapy_consents',
        'questionnaire' => 'jta_therapy_questionnaire',
        'questionari' => 'jta_therapy_questionnaire',
        'questionnaires' => 'jta_therapy_questionnaire',
        'check_periodici' => 'jta_therapy_checks',
        'checks' => 'jta_therapy_checks',
        'reminders' => 'jta_therapy_reminders',
        'promemoria' => 'jta_therapy_reminders',
        'report' => 'jta_therapy_reports',
        'reports' => 'jta_therapy_reports',
    ];

    public static function resolve(string $baseName): string
    {
        if (isset(self::$tableCache[$baseName])) {
            return self::$tableCache[$baseName];
        }

        if (!isset(self::$tableMap[$baseName])) {
            throw new InvalidArgumentException("Tabella '{$baseName}' non mappata.");
        }

        $tableName = self::$tableMap[$baseName];
        $exists = db_fetch_one(
            "SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?",
            [DB_NAME, $tableName]
        );

        if (!$exists) {
            throw new RuntimeException("Tabella '{$tableName}' non trovata nel database");
        }

        self::$tableCache[$baseName] = $tableName;
        return $tableName;
    }

    public static function columns(string $table): array
    {
        if (isset(self::$columnsCache[$table])) {
            return self::$columnsCache[$table];
        }

        $rows = db_fetch_all("SHOW COLUMNS FROM `{$table}`");
        $columns = array_map(static function ($row) {
            return $row['Field'];
        }, $rows);

        self::$columnsCache[$table] = $columns;
        return $columns;
    }

    public static function filterData(string $table, array $data): array
    {
        $columns = array_flip(self::columns($table));
        return array_intersect_key($data, $columns);
    }
}
