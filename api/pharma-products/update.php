<?php
/**
 * API Aggiornamento Promozioni
 * Assistente Farmacia Panel
 */

require_once '../../config/database.php';
require_once '../../includes/auth_middleware.php';

// Verifica accesso farmacista
checkAccess(['pharmacist']);

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Metodo non consentito']);
    exit;
}

try {
    $pharma_id = $_SESSION['pharmacy_id'];
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Se non è JSON, usa POST
    if (!$input) {
        $input = $_POST;
    }
    
    $id = $input['id'] ?? null;
    $sale_price = $input['sale_price'] ?? null;
    $sale_start_date = $input['sale_start_date'] ?? null;
    $sale_end_date = $input['sale_end_date'] ?? null;
    $is_on_sale = $input['is_on_sale'] ?? null;
    
    if (!$id) {
        echo json_encode(['success' => false, 'message' => 'ID promozione richiesto']);
        exit;
    }
    
    // Verifica che la promozione appartenga alla farmacia
    $stmt = $pdo->prepare("SELECT id FROM jta_pharma_prods WHERE id = ? AND pharma_id = ?");
    $stmt->execute([$id, $pharma_id]);
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Promozione non trovata']);
        exit;
    }
    
    // Costruzione query di aggiornamento
    $updateFields = [];
    $params = [];
    
    if ($sale_price !== null) {
        $updateFields[] = "sale_price = ?";
        $params[] = $sale_price;
    }
    
    if ($sale_start_date !== null) {
        $updateFields[] = "sale_start_date = ?";
        $params[] = $sale_start_date;
    }
    
    if ($sale_end_date !== null) {
        $updateFields[] = "sale_end_date = ?";
        $params[] = $sale_end_date;
    }
    
    if ($is_on_sale !== null) {
        $updateFields[] = "is_on_sale = ?";
        $params[] = $is_on_sale;
    }
    

    
    if (empty($updateFields)) {
        echo json_encode(['success' => false, 'message' => 'Nessun campo da aggiornare']);
        exit;
    }
    
    // Aggiungi ID alla fine dei parametri
    $params[] = $id;
    $params[] = $pharma_id;
    
    $sql = "UPDATE jta_pharma_prods SET " . implode(', ', $updateFields) . " WHERE id = ? AND pharma_id = ?";
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute($params);
    
    if ($result) {
        // Log dell'attività
        $action_details = [
            'promotion_id' => $id,
            'updated_fields' => array_keys(array_filter([
                'sale_price' => $sale_price !== null,
                'sale_start_date' => $sale_start_date !== null,
                'sale_end_date' => $sale_end_date !== null,
                'is_on_sale' => $is_on_sale !== null
            ]))
        ];
        
        logActivity('promotion_updated', $action_details);
        
        echo json_encode([
            'success' => true,
            'message' => 'Promozione aggiornata con successo'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Errore durante l\'aggiornamento'
        ]);
    }
    
} catch (Exception $e) {
    error_log("Errore API update promozioni: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Errore interno del server'
    ]);
}

function logActivity($action, $details = null) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO jta_activity_log (user_id, action, details, ip_address, user_agent) 
            VALUES (?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $_SESSION['user_id'] ?? null,
            $action,
            $details ? json_encode($details) : null,
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null
        ]);
    } catch (Exception $e) {
        error_log("Errore logging attività: " . $e->getMessage());
    }
}
?> 