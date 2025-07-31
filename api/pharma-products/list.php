<?php
/**
 * API Lista Prodotti Farmacia
 * Assistente Farmacia Panel
 */

require_once '../../config/database.php';
require_once '../../includes/auth_middleware.php';

// Verifica accesso farmacista
checkAccess(['pharmacist']);

header('Content-Type: application/json');

try {
    // Ottieni farmacia corrente
    $pharmacy = getCurrentPharmacy();
    $pharmacyId = $pharmacy['id'] ?? 0;
    
    if (!$pharmacyId) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Farmacia non trovata'
        ]);
        exit;
    }
    
    // Parametri di paginazione e filtri
    $page = max(1, intval($_GET['page'] ?? 1));
    $limit = max(1, min(100, intval($_GET['limit'] ?? 20)));
    $offset = ($page - 1) * $limit;
    
    $search = trim($_GET['search'] ?? '');
    $category = trim($_GET['category'] ?? '');
    $brand = trim($_GET['brand'] ?? '');
    $status = $_GET['status'] ?? '';
    
    // Costruisci la query base con JOIN per ottenere dati del prodotto globale
    $sql = "SELECT pp.*, gp.category, gp.brand, gp.active_ingredient, gp.dosage_form, gp.strength, gp.package_size, gp.image as global_image 
            FROM jta_pharma_prods pp 
            LEFT JOIN jta_global_prods gp ON pp.product_id = gp.id 
            WHERE pp.pharma_id = ?";
    $params = [$pharmacyId];
    
    // Aggiungi filtri
    if ($search) {
        $sql .= " AND (pp.sku LIKE ? OR pp.name LIKE ? OR pp.description LIKE ? OR gp.active_ingredient LIKE ?)";
        $searchParam = "%{$search}%";
        $params[] = $searchParam;
        $params[] = $searchParam;
        $params[] = $searchParam;
        $params[] = $searchParam;
    }
    
    if ($category) {
        $sql .= " AND gp.category = ?";
        $params[] = $category;
    }
    
    if ($brand) {
        $sql .= " AND gp.brand = ?";
        $params[] = $brand;
    }
    
    if ($status !== '') {
        $sql .= " AND pp.is_active = ?";
        $params[] = intval($status);
    }
    
    // Conta totale record
    $countSql = "SELECT COUNT(*) as total FROM (" . $sql . ") as subquery";
    $total = db_fetch_one($countSql, $params)['total'];
    
    // Aggiungi ordinamento e paginazione
    $sql .= " ORDER BY pp.name ASC LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;
    
    // Esegui query
    $products = db_fetch_all($sql, $params);
    
    // Gestisci immagini per ogni prodotto
    foreach ($products as &$product) {
        // Se il prodotto farmacia ha un'immagine personalizzata, usa quella
        if (!empty($product['image'])) {
            $product['image'] = $product['image'];
        }
        // Altrimenti usa l'immagine del prodotto globale
        elseif (!empty($product['global_image'])) {
            $product['image'] = $product['global_image'];
        }
        // Altrimenti nessuna immagine
        else {
            $product['image'] = null;
        }
        
        // Rimuovi il campo global_image per evitare confusione
        unset($product['global_image']);
    }
    
    // Calcola paginazione
    $totalPages = ceil($total / $limit);
    
    echo json_encode([
        'success' => true,
        'products' => $products,
        'total' => intval($total),
        'current_page' => $page,
        'total_pages' => $totalPages,
        'per_page' => $limit,
        'filters' => [
            'search' => $search,
            'category' => $category,
            'brand' => $brand,
            'status' => $status
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Errore interno del server: ' . $e->getMessage()
    ]);
}
?> 