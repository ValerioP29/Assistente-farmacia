<?php
/**
 * API Aggiorna Prodotto
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
    
    // Validazione campi obbligatori
    $id = intval($_POST['id'] ?? 0);
    $sku = trim($_POST['sku'] ?? '');
    $name = trim($_POST['name'] ?? '');
    
    if ($id <= 0 || empty($sku) || empty($name)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'ID, SKU e Nome sono campi obbligatori'
        ]);
        exit;
    }
    
    // Verifica se prodotto esiste
    $existingProduct = db_fetch_one("SELECT * FROM jta_global_prods WHERE id = ?", [$id]);
    if (!$existingProduct) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Prodotto non trovato'
        ]);
        exit;
    }
    
    // Verifica se SKU già esiste (escludendo il prodotto corrente)
    $existingSku = db_fetch_one("SELECT id FROM jta_global_prods WHERE sku = ? AND id != ?", [$sku, $id]);
    if ($existingSku) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'SKU già esistente'
        ]);
        exit;
    }
    
    // Gestione upload immagine
    $imagePath = $existingProduct['image']; // Mantieni immagine esistente
    $category = trim($_POST['category'] ?? $existingProduct['category'] ?? 'generico');
    
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        try {
            // Elimina immagine precedente se esiste
            deleteProductImage($existingProduct['image']);
            
            // Processa nuova immagine
            $imagePath = processProductImage($_FILES['image'], $category);
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Errore nel caricamento dell\'immagine: ' . $e->getMessage()
            ]);
            exit;
        }
    } elseif (empty($existingProduct['image'])) {
        // Genera immagine placeholder se non c'è un'immagine esistente
        $imagePath = generateProductPlaceholder($name, $category, $sku);
    }
    
    // Prepara dati per aggiornamento
    $data = [
        'sku' => $sku,
        'name' => $name,
        'description' => trim($_POST['description'] ?? ''),
        'image' => $imagePath,
        'category' => trim($_POST['category'] ?? ''),
        'brand' => trim($_POST['brand'] ?? ''),
        'active_ingredient' => trim($_POST['active_ingredient'] ?? ''),
        'dosage_form' => trim($_POST['dosage_form'] ?? ''),
        'strength' => trim($_POST['strength'] ?? ''),
        'package_size' => trim($_POST['package_size'] ?? ''),
        'requires_prescription' => isset($_POST['requires_prescription']) ? 1 : 0,
        'is_active' => isset($_POST['is_active']) ? 1 : 0
    ];
    
    // Aggiorna nel database
    $affected = db()->update('jta_global_prods', $data, 'id = ?', [$id]);
    
    if ($affected === 0) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Nessuna modifica apportata'
        ]);
        exit;
    }
    
    // Log attività
    logActivity('product_updated', [
        'product_id' => $id,
        'sku' => $sku,
        'name' => $name
    ]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Prodotto aggiornato con successo'
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Errore interno del server: ' . $e->getMessage()
    ]);
}

?> 