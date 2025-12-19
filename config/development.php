<?php
/**
 * Configurazione Sviluppo
 * Assistente Farmacia Panel
 */

// Modalità sviluppo
define('DEVELOPMENT_MODE', true);

// Configurazione Applicazione (solo se non già definite)
if (!defined('APP_URL')) define('APP_URL', 'http://localhost:8000');

// Configurazione Database (solo se non già definite)
if (!defined('DB_HOST')) define('DB_HOST', 'localhost');
if (!defined('DB_NAME')) define('DB_NAME', 'jt_assistente_farmacia');
if (!defined('DB_USER')) define('DB_USER', 'root');
if (!defined('DB_PASS')) define('DB_PASS', 'root');

// Configurazione Email (solo se non già definite)
if (!defined('SMTP_HOST')) define('SMTP_HOST', 'smtp.gmail.com');
if (!defined('SMTP_PORT')) define('SMTP_PORT', 587);
if (!defined('SMTP_USER')) define('SMTP_USER', '');
if (!defined('SMTP_PASS')) define('SMTP_PASS', '');
if (!defined('SMTP_FROM')) define('SMTP_FROM', 'noreply@assistentefarmacia.it');

// Configurazione WhatsApp (solo se non già definite)
if (!defined('WHATSAPP_API_KEY')) define('WHATSAPP_API_KEY', '');
if (!defined('WHATSAPP_PHONE_ID')) define('WHATSAPP_PHONE_ID', '');
if (!defined('WHATSAPP_BASE_URL')) define('WHATSAPP_BASE_URL', 'https://waservice.jungleteam.it');

// Configurazione Google Maps (solo se non già definite)
if (!defined('GOOGLE_MAPS_API_KEY')) define('GOOGLE_MAPS_API_KEY', '');

// Configurazione Error Reporting per sviluppo
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/development.log');
?>