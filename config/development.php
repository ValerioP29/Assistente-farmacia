<?php
/**
 * Configurazione Sviluppo
 * Assistente Farmacia Panel
 */

// Abilita modalità sviluppo
if (!defined('DEVELOPMENT_MODE')) {
    define('DEVELOPMENT_MODE', true);
}

// Configurazione error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

// Configurazione sessione per sviluppo (solo se la sessione non è ancora attiva)
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 0);
    ini_set('session.cookie_secure', 0);
}

// Configurazione database per sviluppo (solo se non già definite)
if (!defined('DB_HOST')) define('DB_HOST', 'localhost');
if (!defined('DB_NAME')) define('DB_NAME', 'jt_assistente_farmacia');
if (!defined('DB_USER')) define('DB_USER', 'root');
if (!defined('DB_PASS')) define('DB_PASS', '');

// Configurazione applicazione per sviluppo (solo se non già definite)
if (!defined('APP_URL')) define('APP_URL', 'http://localhost:8000');
if (!defined('APP_DEBUG')) define('APP_DEBUG', true);

// Configurazione upload per sviluppo (solo se non già definite)
if (!defined('UPLOAD_PATH')) define('UPLOAD_PATH', __DIR__ . '/../uploads/');
if (!defined('MAX_FILE_SIZE')) define('MAX_FILE_SIZE', 10 * 1024 * 1024); // 10MB per sviluppo

// Configurazione cache per sviluppo
if (!defined('CACHE_ENABLED')) define('CACHE_ENABLED', false);

// Configurazione logging per sviluppo
if (!defined('LOG_LEVEL')) define('LOG_LEVEL', 'DEBUG');
if (!defined('LOG_FILE')) define('LOG_FILE', __DIR__ . '/../logs/development.log');

// Configurazione email per sviluppo (solo se non già definite)
if (!defined('SMTP_HOST')) define('SMTP_HOST', 'localhost');
if (!defined('SMTP_PORT')) define('SMTP_PORT', 1025); // MailHog per sviluppo
if (!defined('SMTP_USER')) define('SMTP_USER', '');
if (!defined('SMTP_PASS')) define('SMTP_PASS', '');

// Configurazione WhatsApp per sviluppo (solo se non già definite)
if (!defined('WHATSAPP_API_KEY')) define('WHATSAPP_API_KEY', 'test_key');
if (!defined('WHATSAPP_PHONE_ID')) define('WHATSAPP_PHONE_ID', 'test_phone');

// Configurazione Google Maps per sviluppo (solo se non già definite)
if (!defined('GOOGLE_MAPS_API_KEY')) define('GOOGLE_MAPS_API_KEY', 'test_key');

// Configurazione JWT per sviluppo (solo se non già definita)
if (!defined('JWT_SECRET')) define('JWT_SECRET', 'development-secret-key-change-in-production');

// Configurazione CORS per sviluppo
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-CSRF-Token');

// Gestione preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Funzioni di utilità per sviluppo
if (!function_exists('dd')) {
    function dd($var) {
        echo '<pre>';
        var_dump($var);
        echo '</pre>';
        exit;
    }
}

if (!function_exists('d')) {
    function d($var) {
        echo '<pre>';
        var_dump($var);
        echo '</pre>';
    }
}

// Log per sviluppo
if (!function_exists('dev_log')) {
    function dev_log($message, $level = 'INFO') {
        if (defined('LOG_FILE')) {
            $logDir = dirname(LOG_FILE);
            if (!is_dir($logDir)) {
                mkdir($logDir, 0755, true);
            }
            
            $timestamp = date('Y-m-d H:i:s');
            $logMessage = "[{$timestamp}] [{$level}] {$message}" . PHP_EOL;
            file_put_contents(LOG_FILE, $logMessage, FILE_APPEND | LOCK_EX);
        }
        
        if (defined('APP_DEBUG') && APP_DEBUG) {
            error_log("[DEV] {$message}");
        }
    }
}

// Debug function
if (!function_exists('debug')) {
    function debug($var, $label = '') {
        if (defined('APP_DEBUG') && APP_DEBUG) {
            echo '<div style="background: #f0f0f0; border: 1px solid #ccc; padding: 10px; margin: 10px; font-family: monospace;">';
            if ($label) {
                echo '<strong>' . htmlspecialchars($label) . ':</strong><br>';
            }
            echo '<pre>' . htmlspecialchars(print_r($var, true)) . '</pre>';
            echo '</div>';
        }
    }
}

// Performance timer
if (!function_exists('start_timer')) {
    $timers = [];
    
    function start_timer($name) {
        global $timers;
        $timers[$name] = microtime(true);
    }
    
    function end_timer($name) {
        global $timers;
        if (isset($timers[$name])) {
            $duration = microtime(true) - $timers[$name];
            dev_log("Timer {$name}: " . round($duration * 1000, 2) . "ms");
            unset($timers[$name]);
            return $duration;
        }
        return 0;
    }
}

// Database query logger
if (!function_exists('log_query')) {
    function log_query($sql, $params = [], $duration = 0) {
        if (defined('APP_DEBUG') && APP_DEBUG) {
            $logMessage = "SQL: {$sql}";
            if (!empty($params)) {
                $logMessage .= " | Params: " . json_encode($params);
            }
            if ($duration > 0) {
                $logMessage .= " | Duration: " . round($duration * 1000, 2) . "ms";
            }
            dev_log($logMessage, 'SQL');
        }
    }
}

// Memory usage logger
if (!function_exists('log_memory')) {
    function log_memory($label = '') {
        if (defined('APP_DEBUG') && APP_DEBUG) {
            $memory = memory_get_usage(true);
            $peak = memory_get_peak_usage(true);
            $label = $label ? " ({$label})" : '';
            dev_log("Memory{$label}: " . format_bytes($memory) . " | Peak: " . format_bytes($peak), 'MEMORY');
        }
    }
}

// Format bytes
if (!function_exists('format_bytes')) {
    function format_bytes($bytes, $precision = 2) {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }
}

// Inizializza timer globale
start_timer('page_load');

// Log iniziale
dev_log('Development mode enabled', 'INFO');
log_memory('Start');
?> 