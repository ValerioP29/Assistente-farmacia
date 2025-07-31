<?php
/**
 * API Get Prodotto Singolo
 * Assistente Farmacia Panel
 */

require_once '../../config/database.php';
require_once '../../includes/auth_middleware.php';

// Verifica accesso admin
checkAdminAccess();

header('Content-Type: application/json');

try {
    $id = intval($_GET['id'] ?? 0);
    
    if ($id <= 0) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'ID prodotto non valido'
        ]);
        exit;
    }
    
    $sql = "SELECT * FROM jta_global_prods WHERE id = ?";
    $product = db_fetch_one($sql, [$id]);
    
    if (!$product) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Prodotto non trovato'
        ]);
        exit;
    }
    
    echo json_encode([
        'success' => true,
        'product' => $product
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Errore interno del server: ' . $e->getMessage()
    ]);
}
?> 