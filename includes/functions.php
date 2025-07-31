<?php
/**
 * Funzioni di utilità
 * Assistente Farmacia Panel
 */

// Funzioni di autenticazione e accesso
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function isAdmin() {
    return isLoggedIn() && ($_SESSION['user_role'] ?? '') === 'admin';
}

function isPharmacist() {
    return isLoggedIn() && ($_SESSION['user_role'] ?? '') === 'pharmacist';
}

function isUser() {
    return isLoggedIn() && ($_SESSION['user_role'] ?? '') === 'user';
}

function checkAccess($required_roles = ['admin'], $redirect = true) {
    // Se non è loggato
    if (!isLoggedIn()) {
        if ($redirect) {
            saveReturnUrl();
            redirect('login.php', 'Devi effettuare il login per accedere a questa pagina', 'warning');
        }
        return false;
    }
    
    // Se è loggato ma non ha i ruoli richiesti
    $user_role = $_SESSION['user_role'] ?? 'user';
    if (!in_array($user_role, $required_roles)) {
        if ($redirect) {
            redirect('login.php', 'Non hai i permessi per accedere a questa pagina', 'danger');
        }
        return false;
    }
    
    return true;
}

function checkApiAccess($required_roles = ['admin']) {
    // Se non è loggato
    if (!isLoggedIn()) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Autenticazione richiesta']);
        exit;
    }
    
    // Se è loggato ma non ha i ruoli richiesti
    $user_role = $_SESSION['user_role'] ?? 'user';
    if (!in_array($user_role, $required_roles)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Accesso negato']);
        exit;
    }
    
    return true;
}

function saveReturnUrl() {
    if ($_SERVER['REQUEST_URI'] !== '/login.php') {
        $_SESSION['return_url'] = $_SERVER['REQUEST_URI'];
    }
}

function getReturnUrl() {
    $return_url = $_SESSION['return_url'] ?? 'dashboard.php';
    unset($_SESSION['return_url']);
    return $return_url;
}

// Funzioni di redirect e messaggi
function redirect($url, $message = null, $type = 'info') {
    if ($message) {
        $_SESSION['alert'] = [
            'message' => $message,
            'type' => $type
        ];
    }
    session_write_close();
    header('Location: ' . $url);
    exit;
}

// Funzioni di sanitizzazione e validazione
function sanitize($input) {
    if (is_array($input)) {
        return array_map('sanitize', $input);
    }
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

// Funzioni CSRF
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Funzioni di logging
function logActivity($action, $data = []) {
    $user_id = $_SESSION['user_id'] ?? 'system';
    $user_role = $_SESSION['user_role'] ?? 'system';
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    
    $log_data = [
        'action' => $action,
        'user_id' => $user_id,
        'user_role' => $user_role,
        'ip' => $ip,
        'timestamp' => date('Y-m-d H:i:s'),
        'data' => $data
    ];
    
    error_log('ACTIVITY: ' . json_encode($log_data));
}

// Funzioni per farmacia corrente
function getCurrentPharmacy() {
    if (!isset($_SESSION['pharmacy_id'])) {
        return null;
    }
    
    try {
        $sql = "SELECT * FROM jta_pharmas WHERE id = ? AND status != 'deleted'";
        return db_fetch_one($sql, [$_SESSION['pharmacy_id'] ?? 1]);
    } catch (Exception $e) {
        error_log("Errore recupero farmacia corrente: " . $e->getMessage());
        return null;
    }
}

// Funzioni di utilità per date
function formatDate($date, $format = 'd/m/Y H:i') {
    if (!$date) return '';
    return date($format, strtotime($date));
}

function isToday($date) {
    return date('Y-m-d') === date('Y-m-d', strtotime($date));
}

function isYesterday($date) {
    return date('Y-m-d', strtotime('-1 day')) === date('Y-m-d', strtotime($date));
}

// Funzioni di utilità per stringhe
function truncateText($text, $length = 100, $suffix = '...') {
    if (strlen($text) <= $length) {
        return $text;
    }
    return substr($text, 0, $length) . $suffix;
}

function slugify($text) {
    // Rimuovi caratteri speciali e spazi
    $text = preg_replace('/[^a-zA-Z0-9\s-]/', '', $text);
    // Sostituisci spazi con trattini
    $text = preg_replace('/\s+/', '-', $text);
    // Converti in minuscolo
    $text = strtolower($text);
    // Rimuovi trattini multipli
    $text = preg_replace('/-+/', '-', $text);
    // Rimuovi trattini iniziali e finali
    return trim($text, '-');
}

// Genera slug URL unico per farmacia
function generatePharmacySlugUrl($nice_name, $existing_id = null) {
    $base_slug = slugify($nice_name);
    $slug = $base_slug;
    $counter = 1;
    
    // Controlla se lo slug esiste già
    $sql = "SELECT id FROM jta_pharmas WHERE slug_url = ? AND status != 'deleted'";
    $params = [$slug];
    
    if ($existing_id) {
        $sql .= " AND id != ?";
        $params[] = $existing_id;
    }
    
    while (db_fetch_one($sql, $params)) {
        $slug = $base_slug . '-' . $counter;
        $params[0] = $slug;
        $counter++;
    }
    
    return $slug;
}

// Funzioni di utilità per array
function arrayToSelectOptions($array, $selected = null, $empty_option = '') {
    $html = '';
    if ($empty_option) {
        $html .= '<option value="">' . htmlspecialchars($empty_option) . '</option>';
    }
    
    foreach ($array as $value => $label) {
        $is_selected = ($selected == $value) ? ' selected' : '';
        $html .= '<option value="' . htmlspecialchars($value) . '"' . $is_selected . '>' . htmlspecialchars($label) . '</option>';
    }
    
    return $html;
}

/**
 * Imposta un messaggio di alert nella sessione
 */
function setAlert($message, $type = 'info') {
    $_SESSION['alert'] = [
        'message' => $message,
        'type' => $type
    ];
}

// Funzioni di utilità per file
function getFileExtension($filename) {
    return strtolower(pathinfo($filename, PATHINFO_EXTENSION));
}

function isImageFile($filename) {
    $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    return in_array(getFileExtension($filename), $allowed_extensions);
}

function formatFileSize($bytes) {
    $units = ['B', 'KB', 'MB', 'GB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    
    $bytes /= pow(1024, $pow);
    
    return round($bytes, 2) . ' ' . $units[$pow];
}

// Funzioni di utilità per numeri
function formatNumber($number, $decimals = 2) {
    return number_format($number, $decimals, ',', '.');
}

function formatCurrency($amount, $currency = '€') {
    return $currency . ' ' . formatNumber($amount);
}

// Funzioni di utilità per URL
function getCurrentUrl() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    return $protocol . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
}

function getBaseUrl() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    return $protocol . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']);
}

// Funzioni di utilità per debug
function logDebug($message, $data = null) {
    $log_message = date('Y-m-d H:i:s') . ' - ' . $message;
    if ($data) {
        $log_message .= ' - ' . json_encode($data);
    }
    error_log($log_message);
}

// Funzione helper per htmlspecialchars con gestione null
function h($value, $default = '') {
    return htmlspecialchars($value ?? $default, ENT_QUOTES, 'UTF-8');
}

/**
 * Ottiene statistiche dashboard
 */
function getDashboardStats() {
    try {
        $stats = [];
        
        // Se è admin, mostra dati di tutte le farmacie
        if (isAdmin()) {
            // Numero prodotti globali
            $sql = "SELECT COUNT(*) as count FROM jta_global_prods WHERE is_active = 1";
            $result = db_fetch_one($sql);
            $stats['global_products'] = $result['count'];
            
            // Numero utenti totali
            $sql = "SELECT COUNT(*) as count FROM jta_users WHERE status != 'deleted'";
            $result = db_fetch_one($sql);
            $stats['total_users'] = $result['count'];
            
            // Numero farmacie totali
            $sql = "SELECT COUNT(*) as count FROM farmacie";
            $result = db_fetch_one($sql);
            $stats['total_pharmacies'] = $result['count'];
            
            // Richieste in corso totali (in elaborazione + da elaborare)
            $sql = "SELECT COUNT(*) as count FROM prenotazioni WHERE status IN ('in elaborazione', 'da elaborare')";
            $result = db_fetch_one($sql);
            $stats['pending_requests'] = $result['count'];
            
            // Richieste completate totali
            $sql = "SELECT COUNT(*) as count FROM prenotazioni WHERE status = 'completato'";
            $result = db_fetch_one($sql);
            $stats['completed_requests'] = $result['count'];
        } else {
            // Se è farmacista, mostra solo dati della sua farmacia
            $pharmacy = getCurrentPharmacy();
            $pharmacyId = $pharmacy['id'] ?? 0;
            
            // Numero clienti della farmacia
            $sql = "SELECT COUNT(*) as count FROM jta_users WHERE status != 'deleted' AND starred_pharma = ?";
            $result = db_fetch_one($sql, [$pharmacyId]);
            $stats['customers'] = $result['count'];
            
            // Richieste in corso della farmacia (in elaborazione + da elaborare)
            $sql = "SELECT COUNT(*) as count FROM prenotazioni WHERE status IN ('in elaborazione', 'da elaborare') AND farmacia_id = ?";
            $result = db_fetch_one($sql, [$pharmacyId]);
            $stats['pending_requests'] = $result['count'];
            
            // Richieste completate della farmacia
            $sql = "SELECT COUNT(*) as count FROM prenotazioni WHERE status = 'completato' AND farmacia_id = ?";
            $result = db_fetch_one($sql, [$pharmacyId]);
            $stats['completed_requests'] = $result['count'];
            
            // Prodotti della farmacia
            $sql = "SELECT COUNT(*) as count FROM prodotti WHERE farmacia_id = ?";
            $result = db_fetch_one($sql, [$pharmacyId]);
            $stats['products'] = $result['count'];
        }
        
        return $stats;
    } catch (Exception $e) {
        if (isAdmin()) {
            return [
                'global_products' => 0,
                'total_users' => 0,
                'total_pharmacies' => 0,
                'pending_requests' => 0,
                'completed_requests' => 0
            ];
        } else {
            return [
                'customers' => 0,
                'pending_requests' => 0,
                'completed_requests' => 0,
                'products' => 0
            ];
        }
    }
}

/**
 * Ottiene dati per grafico
 */
function getChartData($days = 30) {
    try {
        $data = [];
        
        // Se è admin, mostra dati di tutte le farmacie
        if (isAdmin()) {
            // Richieste totali
            $sql = "SELECT DATE(data_prenotazione) as date, COUNT(*) as count 
                    FROM prenotazioni 
                    WHERE data_prenotazione >= DATE_SUB(NOW(), INTERVAL ? DAY)
                    GROUP BY DATE(data_prenotazione)
                    ORDER BY date";
            
            $results = db_fetch_all($sql, [$days]);
            
            // Richieste completate
            $sql_completed = "SELECT DATE(data_prenotazione) as date, COUNT(*) as count 
                             FROM prenotazioni 
                             WHERE data_prenotazione >= DATE_SUB(NOW(), INTERVAL ? DAY)
                             AND status = 'completato'
                             GROUP BY DATE(data_prenotazione)
                             ORDER BY date";
            
            $results_completed = db_fetch_all($sql_completed, [$days]);
        } else {
            // Se è farmacista, mostra solo dati della sua farmacia
            $pharmacy = getCurrentPharmacy();
            $pharmacyId = $pharmacy['id'] ?? 0;
            
            // Richieste totali
            $sql = "SELECT DATE(data_prenotazione) as date, COUNT(*) as count 
                    FROM prenotazioni 
                    WHERE data_prenotazione >= DATE_SUB(NOW(), INTERVAL ? DAY)
                    AND pharmacy_id = ?
                    GROUP BY DATE(data_prenotazione)
                    ORDER BY date";
            
            $results = db_fetch_all($sql, [$days, $pharmacyId]);
            
            // Richieste completate
            $sql_completed = "SELECT DATE(data_prenotazione) as date, COUNT(*) as count 
                             FROM prenotazioni 
                             WHERE data_prenotazione >= DATE_SUB(NOW(), INTERVAL ? DAY)
                             AND pharmacy_id = ? AND status = 'completato'
                             GROUP BY DATE(data_prenotazione)
                             ORDER BY date";
            
            $results_completed = db_fetch_all($sql_completed, [$days, $pharmacyId]);
        }
        
        // Prepara dati per il grafico
        $data = [];
        foreach ($results as $row) {
            $data['labels'][] = formatDate($row['date'], 'd/m');
            $data['total_requests'][] = (int)$row['count'];
        }
        
        // Prepara dati per richieste completate
        $data['completed_requests'] = [];
        foreach ($results as $row) {
            $completed_count = 0;
            foreach ($results_completed as $completed_row) {
                if ($completed_row['date'] == $row['date']) {
                    $completed_count = (int)$completed_row['count'];
                    break;
                }
            }
            $data['completed_requests'][] = $completed_count;
        }
        
        return $data;
    } catch (Exception $e) {
        return ['labels' => [], 'values' => []];
    }
}

/**
 * Controlla se la farmacia è aperta
 */
function isPharmacyOpen($pharmacyId = null) {
    $hours = getPharmacyHours($pharmacyId);
    if (!$hours) return false;
    
    $today = date('l'); // Nome del giorno in inglese
    $currentTime = date('H:i');
    
    if (!isset($hours[$today])) return false;
    
    $dayHours = $hours[$today];
    
    // Controlla orari mattina
    if (isset($dayHours['mattina'])) {
        $morning = explode('-', $dayHours['mattina']);
        if (count($morning) === 2) {
            if ($currentTime >= $morning[0] && $currentTime <= $morning[1]) {
                return true;
            }
        }
    }
    
    // Controlla orari pomeriggio
    if (isset($dayHours['pomeriggio'])) {
        $afternoon = explode('-', $dayHours['pomeriggio']);
        if (count($afternoon) === 2) {
            if ($currentTime >= $afternoon[0] && $currentTime <= $afternoon[1]) {
                return true;
            }
        }
    }
    
    return false;
}

/**
 * Ottiene il prossimo orario di apertura
 */
function getNextOpeningTime($pharmacyId = null) {
    $hours = getPharmacyHours($pharmacyId);
    if (!$hours) return null;
    
    $today = date('l');
    $currentTime = date('H:i');
    $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
    
    // Cerca oggi
    if (isset($hours[$today])) {
        $dayHours = $hours[$today];
        
        // Controlla pomeriggio se siamo in mattina
        if (isset($dayHours['pomeriggio'])) {
            $afternoon = explode('-', $dayHours['pomeriggio']);
            if (count($afternoon) === 2 && $currentTime < $afternoon[0]) {
                return $afternoon[0];
            }
        }
    }
    
    // Cerca nei prossimi giorni
    $currentIndex = array_search($today, $days);
    for ($i = 1; $i <= 7; $i++) {
        $nextDay = $days[($currentIndex + $i) % 7];
        if (isset($hours[$nextDay])) {
            $dayHours = $hours[$nextDay];
            if (isset($dayHours['mattina'])) {
                $morning = explode('-', $dayHours['mattina']);
                if (count($morning) === 2) {
                    return $morning[0];
                }
            }
        }
    }
    
    return null;
}

/**
 * Ottiene orari farmacia
 */
function getPharmacyHours($pharmacyId = null) {
    if (!$pharmacyId) {
        $pharmacy = getCurrentPharmacy();
        $pharmacyId = $pharmacy['id'] ?? 1;
    }
    
    try {
        $sql = "SELECT working_info FROM jta_pharmas WHERE id = ?";
        $result = db_fetch_one($sql, [$pharmacyId]);
        
        if ($result && $result['working_info']) {
            return json_decode($result['working_info'], true);
        }
        
        return null;
    } catch (Exception $e) {
        return null;
    }
}

/**
 * Formatta orari per visualizzazione
 */
function formatWorkingHours($hours) {
    if (!$hours) return [];
    
    $formatted = [];
    $days = [
        'Monday' => 'Lunedì',
        'Tuesday' => 'Martedì', 
        'Wednesday' => 'Mercoledì',
        'Thursday' => 'Giovedì',
        'Friday' => 'Venerdì',
        'Saturday' => 'Sabato',
        'Sunday' => 'Domenica'
    ];
    
    foreach ($hours as $day => $times) {
        $dayName = $days[$day] ?? $day;
        $timeStr = '';
        
        if (isset($times['mattina'])) {
            $timeStr .= $times['mattina'];
        }
        
        if (isset($times['pomeriggio'])) {
            if ($timeStr) $timeStr .= ' - ';
            $timeStr .= $times['pomeriggio'];
        }
        
        if (!$timeStr) {
            $timeStr = 'Chiuso';
        }
        
        $formatted[$dayName] = $timeStr;
    }
    
    return $formatted;
}
?>
