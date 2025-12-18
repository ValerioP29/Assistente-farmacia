<?php
/**
 * API - Statistiche Richieste
 * Assistente Farmacia Panel
 */

require_once '../../config/database.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth_middleware.php';

// Verifica autenticazione
requireApiAuth(['admin', 'pharmacist']);

header('Content-Type: application/json');

try {
    $db = Database::getInstance();

    // Determina contesto
    $userRole = $_SESSION['user_role'] ?? null;

    if ($userRole === 'pharmacist') {
        $pharmacyId = current_pharmacy_id();
    } else {
        // admin: contesto globale
        $pharmacyId = null;
    }

    $where = ['deleted_at IS NULL'];
    $params = [];

    if ($pharmacyId !== null) {
        $where[] = 'pharma_id = ?';
        $params[] = $pharmacyId;
    }

    $whereSql = implode(' AND ', $where);

    $sql = "
        SELECT status, COUNT(*) as count
        FROM jta_requests
        WHERE {$whereSql}
        GROUP BY status
    ";

    $results = $db->fetchAll($sql, $params);

    $stats = [
        'pending' => 0,
        'processing' => 0,
        'completed' => 0,
        'rejected' => 0,
        'cancelled' => 0,
        'total' => 0
    ];

    foreach ($results as $row) {
        switch ($row['status']) {
            case 0:
                $stats['pending'] = (int)$row['count'];
                break;
            case 1:
                $stats['processing'] = (int)$row['count'];
                break;
            case 2:
                $stats['completed'] = (int)$row['count'];
                break;
            case 3:
                $stats['rejected'] = (int)$row['count'];
                break;
            case 4:
                $stats['cancelled'] = (int)$row['count'];
                break;
        }
    }

    $stats['total'] = array_sum($stats);

    $typeSql = "
        SELECT request_type, COUNT(*) as count
        FROM jta_requests
        WHERE {$whereSql}
        GROUP BY request_type
    ";

    $typeResults = $db->fetchAll($typeSql, $params);
    $typeStats = [];
    
    foreach ($typeResults as $row) {
        $typeStats[$row['request_type']] = (int)$row['count'];
    }

    $topPharmacies = [];

    if ($pharmacyId === null) {
        $pharmacySql = "
            SELECT 
                p.business_name,
                p.nice_name,
                COUNT(r.id) as count
            FROM jta_requests r
            LEFT JOIN jta_pharmas p ON r.pharma_id = p.id
            WHERE r.deleted_at IS NULL
            GROUP BY r.pharma_id, p.business_name, p.nice_name
            ORDER BY count DESC
            LIMIT 5
        ";

        $topPharmacies = $db->fetchAll($pharmacySql);
    }

    echo json_encode([
        'success' => true,
        'data' => [
            'status_stats' => $stats,
            'type_stats' => $typeStats,
            'top_pharmacies' => $topPharmacies
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?> 