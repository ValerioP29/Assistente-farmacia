<?php
/**
 * Utility per risolvere tabelle e colonne dinamicamente
 */

class AdesioneTableResolver
{
    private static array $tableCache = [];
    private static array $columnsCache = [];

    public static function resolve(string $baseName): string
    {
        if (isset(self::$tableCache[$baseName])) {
            return self::$tableCache[$baseName];
        }

        $candidates = [
            'jta_' . $baseName,
            'jta_' . $baseName . 's',
            $baseName,
            $baseName . 's',
        ];

        foreach ($candidates as $candidate) {
            $exists = db_fetch_one(
                "SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?",
                [DB_NAME, $candidate]
            );
            if ($exists) {
                self::$tableCache[$baseName] = $candidate;
                return $candidate;
            }
        }

        throw new Exception("Tabella '{$baseName}' non trovata nel database");
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

    public static function hasColumn(string $table, string $column): bool
    {
        return in_array($column, self::columns($table), true);
    }

    public static function firstAvailableColumn(string $table, array $candidates): ?string
    {
        foreach ($candidates as $candidate) {
            if (self::hasColumn($table, $candidate)) {
                return $candidate;
            }
        }
        return null;
    }

    public static function filterData(string $table, array $data): array
    {
        $columns = array_flip(self::columns($table));
        return array_intersect_key($data, $columns);
    }
}
