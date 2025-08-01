<?php
/**
 * API Statistiche Promozioni
 * Assistente Farmacia Panel
 */

require_once '../../config/database.php';
require_once '../../includes/auth_middleware.php';

// Verifica accesso farmacista
checkAccess(['pharmacist']);

header('Content-Type: application/json');

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    // Ottieni farmacia corrente
    $pharmacy = getCurrentPharmacy();
    $pharma_id = $pharmacy['id'] ?? $_SESSION['pharmacy_id'] ?? null;
    
    // Debug: log delle informazioni di sessione
    error_log("Debug sessione - pharmacy_id: " . ($pharma_id ?? 'null') . ", user_id: " . ($_SESSION['user_id'] ?? 'null') . ", user_role: " . ($_SESSION['user_role'] ?? 'null'));
    
    // Verifica che pharma_id sia valido
    if (!$pharma_id) {
        throw new Exception('ID farmacia non valido');
    }
    
    // Debug: verifica se esistono prodotti per questa farmacia
    $debug_stmt = $pdo->prepare("SELECT COUNT(*) as total FROM jta_pharma_prods WHERE pharma_id = ?");
    $debug_stmt->execute([$pharma_id]);
    $total_products = $debug_stmt->fetchColumn();
    error_log("Debug - Totale prodotti per farmacia {$pharma_id}: {$total_products}");
    
    // Debug: verifica prodotti in promozione
    $debug_stmt = $pdo->prepare("SELECT COUNT(*) as total FROM jta_pharma_prods WHERE pharma_id = ? AND is_on_sale = 1");
    $debug_stmt->execute([$pharma_id]);
    $total_on_sale = $debug_stmt->fetchColumn();
    error_log("Debug - Prodotti in promozione per farmacia {$pharma_id}: {$total_on_sale}");
    
    // Debug: mostra alcuni esempi di prodotti in promozione
    $debug_stmt = $pdo->prepare("
        SELECT id, name, is_on_sale, sale_start_date, sale_end_date, price, sale_price 
        FROM jta_pharma_prods 
        WHERE pharma_id = ? AND is_on_sale = 1 
        LIMIT 3
    ");
    $debug_stmt->execute([$pharma_id]);
    $sample_products = $debug_stmt->fetchAll();
    error_log("Debug - Esempi prodotti in promozione: " . json_encode($sample_products));
    
    // Promozioni attive (attualmente in corso)
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count
        FROM jta_pharma_prods 
        WHERE pharma_id = ? 
        AND is_on_sale = 1 
        AND (
            (sale_start_date IS NULL AND sale_end_date IS NULL) OR
            (DATE(sale_start_date) <= CURDATE() AND DATE(sale_end_date) >= CURDATE())
        )
    ");
    $stmt->execute([$pharma_id]);
    $active_promotions = $stmt->fetchColumn();
    error_log("Debug - Promozioni attive: {$active_promotions}");
    
    // Promozioni in arrivo (future)
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count
        FROM jta_pharma_prods 
        WHERE pharma_id = ? 
        AND is_on_sale = 1 
        AND sale_start_date IS NOT NULL 
        AND DATE(sale_start_date) > CURDATE()
    ");
    $stmt->execute([$pharma_id]);
    $upcoming_promotions = $stmt->fetchColumn();
    error_log("Debug - Promozioni in arrivo: {$upcoming_promotions}");
    
    // Promozioni in scadenza (scadono entro 7 giorni)
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count
        FROM jta_pharma_prods 
        WHERE pharma_id = ? 
        AND is_on_sale = 1 
        AND sale_end_date IS NOT NULL 
        AND DATE(sale_end_date) BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
    ");
    $stmt->execute([$pharma_id]);
    $expiring_promotions = $stmt->fetchColumn();
    error_log("Debug - Promozioni in scadenza: {$expiring_promotions}");
    
    // Sconto medio delle promozioni attive
    $stmt = $pdo->prepare("
        SELECT AVG(
            CASE 
                WHEN price > 0 AND sale_price > 0 
                THEN ((price - sale_price) / price) * 100 
                ELSE 0 
            END
        ) as avg_discount
        FROM jta_pharma_prods 
        WHERE pharma_id = ? 
        AND is_on_sale = 1 
        AND (
            (sale_start_date IS NULL AND sale_end_date IS NULL) OR
            (DATE(sale_start_date) <= CURDATE() AND DATE(sale_end_date) >= CURDATE())
        )
        AND price > 0 
        AND sale_price > 0
    ");
    $stmt->execute([$pharma_id]);
    $average_discount = round($stmt->fetchColumn(), 1);
    error_log("Debug - Sconto medio: {$average_discount}%");
    
    echo json_encode([
        'success' => true,
        'active_promotions' => (int)$active_promotions,
        'upcoming_promotions' => (int)$upcoming_promotions,
        'expiring_promotions' => (int)$expiring_promotions,
        'average_discount' => (float)$average_discount
    ]);
    
} catch (Exception $e) {
    error_log("Errore API stats promozioni: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Errore interno del server: ' . $e->getMessage()
    ]);
} catch (Error $e) {
    error_log("Errore fatale API stats promozioni: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Errore fatale del server'
    ]);
}
?> 