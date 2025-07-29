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
    if ($_SERVER['REQUEST_URI'] !== '/login.php' && $_SERVER['REQUEST_URI'] !== '/login.html') {
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
?>
