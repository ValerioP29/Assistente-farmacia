<?php
/**
 * Script di test per inserire richieste di esempio
 * Assistente Farmacia Panel
 */

require_once 'config/database.php';
require_once 'includes/functions.php';

try {
    $db = Database::getInstance();
    
    // Verifica che esistano farmacie e utenti
    $pharmacies = $db->fetchAll("SELECT id FROM jta_pharmas WHERE status = 'active' LIMIT 3");
    $users = $db->fetchAll("SELECT id FROM jta_users WHERE role = 'pharmacist' AND status = 'active' LIMIT 3");
    
    if (empty($pharmacies)) {
        echo "Errore: Nessuna farmacia trovata nel database.\n";
        echo "Esegui prima l'installazione o aggiungi alcune farmacie.\n";
        exit;
    }
    
    if (empty($users)) {
        echo "Errore: Nessun utente farmacista trovato nel database.\n";
        echo "Esegui prima l'installazione o aggiungi alcuni utenti.\n";
        exit;
    }
    
    // Richieste di esempio
    $sampleRequests = [
        [
            'request_type' => 'event',
            'user_id' => $users[0]['id'],
            'pharma_id' => $pharmacies[0]['id'],
            'message' => 'Richiesta per organizzare un evento di sensibilizzazione sulla prevenzione cardiovascolare. Vorremmo collaborare con la farmacia per promuovere uno stile di vita sano.',
            'metadata' => json_encode([
                'event_date' => '2024-02-15',
                'expected_attendees' => 50,
                'location' => 'Sala comunale',
                'notes' => [
                    [
                        'text' => 'Richiesta ricevuta e in fase di valutazione',
                        'status' => 0,
                        'updated_by' => 'admin',
                        'updated_at' => date('Y-m-d H:i:s')
                    ]
                ]
            ]),
            'status' => 0,
            'created_at' => date('Y-m-d H:i:s', strtotime('-3 days'))
        ],
        [
            'request_type' => 'service',
            'user_id' => $users[0]['id'],
            'pharma_id' => $pharmacies[0]['id'],
            'message' => 'Richiesta per implementare un servizio di consegna a domicilio per pazienti anziani e disabili. Servirebbe un servizio di consegna farmaci e prodotti sanitari.',
            'metadata' => json_encode([
                'service_type' => 'home_delivery',
                'target_audience' => 'elderly_disabled',
                'coverage_area' => 'Comune di Milano',
                'notes' => [
                    [
                        'text' => 'Servizio approvato e in fase di implementazione',
                        'status' => 1,
                        'updated_by' => 'admin',
                        'updated_at' => date('Y-m-d H:i:s', strtotime('-1 day'))
                    ]
                ]
            ]),
            'status' => 1,
            'created_at' => date('Y-m-d H:i:s', strtotime('-5 days'))
        ],
        [
            'request_type' => 'promos',
            'user_id' => $users[0]['id'],
            'pharma_id' => $pharmacies[0]['id'],
            'message' => 'Richiesta per promuovere una campagna sconti su integratori vitaminici e prodotti per il benessere. Vorremmo creare un pacchetto promozionale per la primavera.',
            'metadata' => json_encode([
                'promo_type' => 'seasonal',
                'discount_percentage' => 20,
                'valid_until' => '2024-04-30',
                'notes' => [
                    [
                        'text' => 'Promozione completata con successo',
                        'status' => 2,
                        'updated_by' => 'admin',
                        'updated_at' => date('Y-m-d H:i:s', strtotime('-2 hours'))
                    ]
                ]
            ]),
            'status' => 2,
            'created_at' => date('Y-m-d H:i:s', strtotime('-10 days'))
        ],
        [
            'request_type' => 'reservation',
            'user_id' => $users[0]['id'],
            'pharma_id' => $pharmacies[0]['id'],
            'message' => 'Richiesta per prenotare una consulenza farmaceutica personalizzata per un paziente con diabete. Il paziente necessita di consigli su gestione farmaci e alimentazione.',
            'metadata' => json_encode([
                'consultation_type' => 'diabetes_management',
                'patient_age' => 65,
                'preferred_date' => '2024-02-10',
                'notes' => [
                    [
                        'text' => 'Prenotazione rifiutata per indisponibilità del personale',
                        'status' => 3,
                        'updated_by' => 'admin',
                        'updated_at' => date('Y-m-d H:i:s', strtotime('-1 hour'))
                    ]
                ]
            ]),
            'status' => 3,
            'created_at' => date('Y-m-d H:i:s', strtotime('-2 days'))
        ],
        [
            'request_type' => 'event',
            'user_id' => $users[0]['id'],
            'pharma_id' => $pharmacies[0]['id'],
            'message' => 'Richiesta per organizzare un workshop sulla corretta assunzione dei farmaci. Evento annullato per motivi organizzativi.',
            'metadata' => json_encode([
                'event_type' => 'workshop',
                'cancellation_reason' => 'organizational_issues',
                'notes' => [
                    [
                        'text' => 'Evento annullato su richiesta del cliente',
                        'status' => 4,
                        'updated_by' => 'admin',
                        'updated_at' => date('Y-m-d H:i:s')
                    ]
                ]
            ]),
            'status' => 4,
            'created_at' => date('Y-m-d H:i:s', strtotime('-1 day'))
        ]
    ];
    
    // Inserisci le richieste
    $inserted = 0;
    foreach ($sampleRequests as $request) {
        try {
            $db->insert('jta_requests', $request);
            $inserted++;
            echo "✅ Richiesta inserita: {$request['request_type']} - {$request['message']}\n";
        } catch (Exception $e) {
            echo "❌ Errore inserimento richiesta: " . $e->getMessage() . "\n";
        }
    }
    
    echo "\n🎉 Inserimento completato!\n";
    echo "📊 Inserite {$inserted} richieste di esempio.\n";
    echo "🌐 Ora puoi testare il pannello richieste visitando: richieste.php\n";
    
} catch (Exception $e) {
    echo "❌ Errore: " . $e->getMessage() . "\n";
}
?> 