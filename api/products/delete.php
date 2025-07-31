<?php
/**
 * API Elimina Prodotto
 * Assistente Farmacia Panel
 */

require_once '../../config/database.php';
require_once '../../includes/auth_middleware.php';
require_once '../../includes/image_manager.php';

// Verifica accesso admin
checkAdminAccess();

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode([
            'success' => false,
            'message' => 'Metodo non consentito'
        ]);
        exit;
    }
    
    // Leggi input JSON
    $input = json_decode(file_get_contents('php://input'), true);
    $id = intval($input['id'] ?? 0);
    
    if ($id <= 0) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'ID prodotto non valido'
        ]);
        exit;
    }
    
    // Verifica se prodotto esiste
    $product = db_fetch_one("SELECT * FROM jta_global_prods WHERE id = ?", [$id]);
    if (!$product) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Prodotto non trovato'
        ]);
        exit;
    }
    
    // Elimina immagine se presente
    if ($product['image']) {
        deleteProductImage($product['image']);
    }
    
    // Elimina dal database
    $affected = db()->delete('jta_global_prods', 'id = ?', [$id]);
    
    if ($affected === 0) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Errore nell\'eliminazione del prodotto'
        ]);
        exit;
    }
    
    // Log attività
    logActivity('product_deleted', [
        'product_id' => $id,
        'sku' => $product['sku'],
        'name' => $product['name']
    ]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Prodotto eliminato con successo'
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Errore interno del server: ' . $e->getMessage()
    ]);
}

?> 