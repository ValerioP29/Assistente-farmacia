# Configurazione WhatsApp

## Panoramica

Il sistema WhatsApp è stato integrato nel pannello di gestione farmacia per permettere la connessione e gestione del servizio WhatsApp Business.

## Configurazione

### 1. Configurazione URL Servizi

Modifica i file di configurazione per impostare l'URL base del servizio WhatsApp:

#### File: `config/database.php`
```php
// Configurazione WhatsApp
if (!defined('WHATSAPP_BASE_URL')) define('WHATSAPP_BASE_URL', 'https://waservice.jungleteam.it');
```

#### File: `config/development.php` (per sviluppo)
```php
// Configurazione WhatsApp per sviluppo
if (!defined('WHATSAPP_BASE_URL')) define('WHATSAPP_BASE_URL', 'https://waservice.jungleteam.it');
```

### 2. URL Configurabili

- **WHATSAPP_BASE_URL**: URL base del servizio WhatsApp
  - Status/Disconnect: `{WHATSAPP_BASE_URL}/status` e `{WHATSAPP_BASE_URL}/disconnect`
  - QR Code: `{WHATSAPP_BASE_URL}/qr`

## Funzionalità

### 1. Controllo Stato Connessione
- **Endpoint**: `api/whatsapp/check-status.php`
- **Metodo**: GET
- **Funzione**: Verifica se WhatsApp è connesso

### 2. Generazione QR Code
- **Endpoint**: `api/whatsapp/get-qr.php`
- **Metodo**: POST
- **Funzione**: Genera QR code per la connessione

### 3. Disconnessione
- **Endpoint**: `api/whatsapp/disconnect.php`
- **Metodo**: POST
- **Funzione**: Disconnette WhatsApp

## Struttura File

```
whatsapp.php                    # Pagina principale WhatsApp
├── assets/js/core/pharma_wa.js # Script JavaScript frontend
└── api/whatsapp/
    ├── functions.php           # Funzioni comuni
    ├── check-status.php        # API controllo stato
    ├── get-qr.php             # API generazione QR
    └── disconnect.php         # API disconnessione
```

## Utilizzo

1. **Accesso**: Vai su "WhatsApp" nel menu laterale
2. **Connessione**: Inquadra il QR code con WhatsApp
3. **Stato**: Il sistema controlla automaticamente lo stato ogni 20 secondi
4. **Disconnessione**: Usa il pulsante "Disconnetti" per scollegare

## Sicurezza

- Tutte le API richiedono autenticazione
- Le richieste sono validate tramite sessione
- Gli URL dei servizi sono configurabili per ambiente

## Note Tecniche

- Il sistema utilizza cURL per le chiamate API esterne
- SSL verification è disabilitata per localhost
- Il polling automatico si attiva solo quando WhatsApp è disconnesso
- Tutti i messaggi di errore sono localizzati in italiano 