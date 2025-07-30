<?php
/**
 * Script per avviare il server di sviluppo locale
 * Assistente Farmacia Panel
 */

// Configurazione del server
$host = 'localhost';
$port = 8000;
$documentRoot = __DIR__;
$phpVersion = PHP_VERSION;

// Colori per output console
$colors = [
    'green' => "\033[32m",
    'yellow' => "\033[33m",
    'blue' => "\033[34m",
    'red' => "\033[31m",
    'cyan' => "\033[36m",
    'reset' => "\033[0m",
    'bold' => "\033[1m"
];

// Funzione per output colorato
function coloredOutput($text, $color = 'reset') {
    global $colors;
    return $colors[$color] . $text . $colors['reset'];
}

// Header informativo
echo coloredOutput("╔══════════════════════════════════════════════════════════════╗\n", 'cyan');
echo coloredOutput("║                    ASSISTENTE FARMACIA PANEL                ║\n", 'cyan');
echo coloredOutput("║                     Server di Sviluppo                      ║\n", 'cyan');
echo coloredOutput("╚══════════════════════════════════════════════════════════════╝\n", 'cyan');
echo "\n";

// Informazioni di sistema
echo coloredOutput("📋 Informazioni Sistema:\n", 'bold');
echo coloredOutput("   • PHP Version: ", 'yellow') . $phpVersion . "\n";
echo coloredOutput("   • Document Root: ", 'yellow') . $documentRoot . "\n";
echo coloredOutput("   • Host: ", 'yellow') . $host . "\n";
echo coloredOutput("   • Porta: ", 'yellow') . $port . "\n";
echo "\n";

// Verifica requisiti
echo coloredOutput("🔍 Verifica Requisiti:\n", 'bold');

// Verifica estensioni PHP necessarie
$requiredExtensions = ['json', 'mbstring', 'openssl'];
$missingExtensions = [];

foreach ($requiredExtensions as $ext) {
    if (!extension_loaded($ext)) {
        $missingExtensions[] = $ext;
        echo coloredOutput("   ❌ Estensione mancante: ", 'red') . $ext . "\n";
    } else {
        echo coloredOutput("   ✅ Estensione: ", 'green') . $ext . "\n";
    }
}

if (!empty($missingExtensions)) {
    echo "\n" . coloredOutput("⚠️  Attenzione: Alcune estensioni PHP sono mancanti!\n", 'red');
    echo coloredOutput("   Installa le estensioni mancanti per un funzionamento ottimale.\n", 'yellow');
}

echo "\n";

// Verifica file principali
echo coloredOutput("📁 Verifica File Principali:\n", 'bold');
$mainFiles = [
    'dashboard.html' => 'Dashboard principale',
    'login.php' => 'Pagina di login',
    'prodotti.html' => 'Gestione prodotti',
    'prenotazioni.html' => 'Sistema prenotazioni',
    'css/dashboard.css' => 'Stili dashboard',
    'js/navbar.js' => 'Navigazione'
];

foreach ($mainFiles as $file => $description) {
    if (file_exists($file)) {
        echo coloredOutput("   ✅ ", 'green') . $file . " - " . $description . "\n";
    } else {
        echo coloredOutput("   ❌ ", 'red') . $file . " - " . $description . " (MANCANTE)\n";
    }
}

echo "\n";

// URL di accesso
$url = "http://{$host}:{$port}";
echo coloredOutput("🌐 URL di Accesso:\n", 'bold');
echo coloredOutput("   • Dashboard: ", 'yellow') . $url . "/dashboard.html\n";
echo coloredOutput("   • Login: ", 'yellow') . $url . "/login.php\n";
echo coloredOutput("   • Prodotti: ", 'yellow') . $url . "/prodotti.html\n";
echo coloredOutput("   • Prenotazioni: ", 'yellow') . $url . "/prenotazioni.html\n";
echo "\n";

// Istruzioni
echo coloredOutput("📖 Istruzioni:\n", 'bold');
echo coloredOutput("   1. Apri il browser e vai su: ", 'cyan') . $url . "\n";
echo coloredOutput("   2. Premi CTRL+C per fermare il server\n");
echo coloredOutput("   3. Il server si aggiorna automaticamente quando modifichi i file\n");
echo "\n";

// Avvisi
echo coloredOutput("⚠️  Avvisi:\n", 'bold');
echo coloredOutput("   • Questo è un server di sviluppo, NON usare in produzione\n");
echo coloredOutput("   • Assicurati che la porta {$port} sia libera\n");
echo coloredOutput("   • Per problemi di CORS, usa il browser in modalità sviluppatore\n");
echo "\n";

// Separatore
echo coloredOutput("══════════════════════════════════════════════════════════════════\n", 'cyan');
echo coloredOutput("🚀 Avvio server in corso...\n", 'green');
echo coloredOutput("══════════════════════════════════════════════════════════════════\n", 'cyan');
echo "\n";

// Comando per avviare il server
$command = "php -S {$host}:{$port} -t {$documentRoot}";

// Output del comando
echo coloredOutput("Comando eseguito: ", 'yellow') . $command . "\n\n";

// Avvia il server
echo coloredOutput("Server avviato! Apri il browser su: ", 'green') . $url . "\n";
echo coloredOutput("Premi CTRL+C per fermare il server\n\n", 'yellow');

// Esegui il comando
system($command);
?> 