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
    $pharma_id = $_SESSION['pharma_id'];
    
    // Promozioni attive (attualmente in corso)
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count
        FROM jta_pharma_prods 
        WHERE pharma_id = ? 
        AND is_on_sale = 1 
        AND DATE(sale_start_date) <= CURDATE() 
        AND DATE(sale_end_date) >= CURDATE()
    ");
    $stmt->execute([$pharma_id]);
    $active_promotions = $stmt->fetchColumn();
    
    // Promozioni in arrivo (future)
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count
        FROM jta_pharma_prods 
        WHERE pharma_id = ? 
        AND is_on_sale = 1 
        AND DATE(sale_start_date) > CURDATE()
    ");
    $stmt->execute([$pharma_id]);
    $upcoming_promotions = $stmt->fetchColumn();
    
    // Promozioni in scadenza (scadono entro 7 giorni)
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count
        FROM jta_pharma_prods 
        WHERE pharma_id = ? 
        AND is_on_sale = 1 
        AND DATE(sale_end_date) BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
    ");
    $stmt->execute([$pharma_id]);
    $expiring_promotions = $stmt->fetchColumn();
    
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
        AND DATE(sale_start_date) <= CURDATE() 
        AND DATE(sale_end_date) >= CURDATE()
        AND price > 0 
        AND sale_price > 0
    ");
    $stmt->execute([$pharma_id]);
    $average_discount = round($stmt->fetchColumn(), 1);
    
    echo json_encode([
        'success' => true,
        'active_promotions' => (int)$active_promotions,
        'upcoming_promotions' => (int)$upcoming_promotions,
        'expiring_promotions' => (int)$expiring_promotions,
        'average_discount' => (float)$average_discount
    ]);
    
} catch (Exception $e) {
    error_log("Errore API stats promozioni: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Errore interno del server'
    ]);
}
?> 