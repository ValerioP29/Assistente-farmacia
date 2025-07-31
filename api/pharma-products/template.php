<?php
/**
 * API Template CSV Prodotti Farmacia
 * Assistente Farmacia Panel
 */

require_once '../../config/database.php';
require_once '../../includes/auth_middleware.php';

// Verifica accesso farmacista
checkAccess(['pharmacist']);

try {
    // Imposta headers per download CSV
    $filename = 'template_prodotti_farmacia.csv';
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    // Output BOM per UTF-8
    echo "\xEF\xBB\xBF";
    
    // Apri output stream
    $output = fopen('php://output', 'w');
    
    // Headers CSV
    fputcsv($output, [
        'SKU',
        'Nome',
        'Descrizione',
        'Prezzo',
        'Prezzo Scontato',
        'Stato (1=Attivo, 0=Inattivo)'
    ]);
    
    // Esempi di dati
    fputcsv($output, [
        'PAR001-FARM1',
        'Paracetamolo 500mg',
        'Antidolorifico e antipiretico',
        '8.50',
        '7.20',
        '1'
    ]);
    
    fputcsv($output, [
        'IBU001-FARM1',
        'Ibuprofene 400mg',
        'Antinfiammatorio non steroideo',
        '12.30',
        '',
        '1'
    ]);
    
    fputcsv($output, [
        'VIT001-FARM1',
        'Vitamina C 1000mg',
        'Integratore alimentare',
        '18.90',
        '15.20',
        '1'
    ]);
    
    fclose($output);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Errore interno del server: ' . $e->getMessage()
    ]);
}
?> 