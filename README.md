# Assistente Farmacia Panel

## 📋 Panoramica del Progetto

**Assistente Farmacia Panel** è un sistema di gestione completo per farmacie che permette agli amministratori di gestire utenti, farmacie e controllare l'accesso alle risorse del sistema.

## 🏗️ Architettura del Sistema

### Struttura Database
- **`jta_users`** - Gestione utenti e accessi al sistema
- **`jta_pharmas`** - Dati anagrafici delle farmacie

### Ruoli Utente
- **`admin`** - Accesso completo a tutte le funzionalità
- **`pharmacist`** - Accesso limitato alle funzionalità della propria farmacia
- **`user`** - Accesso limitato ai propri dati

### Stati Utente
- **`active`** - Utente può accedere al sistema
- **`inactive`** - Utente bloccato, accesso negato
- **`deleted`** - Soft delete, utente non visibile

## 🔐 Sistema di Controllo Accessi

### Funzionalità
- ✅ Controllo autenticazione per pagine web e API
- ✅ Controllo ruoli con middleware centralizzato
- ✅ Controllo accesso a risorse specifiche
- ✅ Messaggi di avviso personalizzati
- ✅ Log degli accessi non autorizzati
- ✅ Redirect automatico alla login
- ✅ Return URL (reindirizza alla pagina originale dopo il login)

### Utilizzo

#### Per Pagine Web
```php
<?php
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/auth_middleware.php';

// Controllo accesso (PRIMA di qualsiasi output)
requireAdmin(); // Solo admin
requirePharmacistOrAdmin(); // Admin o farmacista
requireLogin(); // Qualsiasi utente autenticato

require_once 'includes/header.php';
?>
```

#### Per API
```php
<?php
session_start();
require_once '../includes/auth_middleware.php';
header('Content-Type: application/json');

requireApiAuth(['admin']); // Solo admin
requireApiAuth(['admin', 'pharmacist']); // Admin o farmacista
?>
```

### Funzioni Disponibili
- `checkAccess($required_roles, $redirect = true)` - Controllo generico
- `checkApiAccess($required_roles)` - Controllo per API
- `requireLogin()` - Qualsiasi utente autenticato
- `requireAdmin()` - Solo amministratori
- `requirePharmacistOrAdmin()` - Farmacisti o amministratori
- `canAccessResource($resource, $resource_id = null)` - Controlla accesso a risorsa specifica

## 👥 Gestione Utenti

### Funzionalità CRUD Complete
- ✅ **Creazione** - Form con validazione completa
- ✅ **Lettura** - Lista con filtri e ricerca
- ✅ **Modifica** - Form con tutti i campi modificabili
- ✅ **Eliminazione** - Soft delete con conferma

### Campi Utente
- **Dati personali**: Nome, Cognome, Email, Telefono
- **Accesso**: Username, Password, Ruolo
- **Stato**: Attivo/Inattivo
- **Associazione**: Farmacia (opzionale per tutti i ruoli)

### API Endpoints
- `api/users/add.php` - Creazione utente
- `api/users/get.php` - Recupero dati utente
- `api/users/edit.php` - Modifica utente
- `api/users/delete.php` - Eliminazione utente
- `api/users/list.php` - Lista utenti
- `api/users/login-as.php` - Accesso come utente
- `api/users/return-admin.php` - Ritorno all'admin
- `api/users/change-pharmacy.php` - Cambio farmacia

### Validazioni
- ✅ Username unico
- ✅ Email unica
- ✅ Password minimo 6 caratteri
- ✅ Email valida
- ✅ Ruolo valido (pharmacist/user)
- ✅ Stato valido (active/inactive)

## 🏥 Gestione Farmacie

### Funzionalità CRUD Complete
- ✅ **Creazione** - Form con dati anagrafici completi
- ✅ **Lettura** - Lista con filtri e ricerca
- ✅ **Modifica** - Form con tutti i campi modificabili
- ✅ **Eliminazione** - Soft delete con conferma

### Campi Farmacia
- **Dati base**: Nome, Nome aziendale, Email, Telefono
- **Localizzazione**: Città, Indirizzo, Coordinate GPS (lat,lng)
- **Contenuti**: Descrizione, Orari di lavoro, Prompt personalizzato
- **Media**: Avatar, Cover, Immagine bot (upload file)
- **Stato**: Attivo/Inattivo/Eliminato

### API Endpoints
- `api/pharmacies/add.php` - Creazione farmacia
- `api/pharmacies/get.php` - Recupero dati farmacia (per modifica)
- `api/pharmacies/edit.php` - Modifica farmacia
- `api/pharmacies/delete.php` - Eliminazione farmacia
- `api/pharmacies/list.php` - Lista farmacie
- `api/pharmacies/upload-image.php` - Upload immagini farmacia

### Validazioni
- ✅ Nome unico
- ✅ Email valida
- ✅ Telefono valido
- ✅ Stato valido (active/inactive/deleted)

## 🗄️ Ottimizzazioni Database

### Tabella `jta_users` (Ottimizzata)
**Problemi risolti:**
- ❌ Colonne duplicate: `phone_number` e `phone`
- ❌ Campi stato ridondanti: `status_id`, `is_active`, `is_deleted`

**Soluzioni implementate:**
- ✅ Unificazione telefono: mantenuto solo `phone`
- ✅ Unificazione stato: nuovo campo `status` ENUM
- ✅ Migrazione dati automatica
- ✅ Backup completo prima delle modifiche

**Struttura finale:**
```sql
jta_users:
- id, slug_name, phone, email, role, status
- password, name, surname, gender, born_date
- starred_pharma, init_profiling
- created_at, updated_at, last_access, last_notification
```

### Tabella `jta_pharmas` (Ottimizzata)
**Problemi risolti:**
- ❌ Campi non necessari: `password`, `last_access`, `slug_name`

**Soluzioni implementate:**
- ✅ Rimozione campi non necessari per dati anagrafici
- ✅ Mantenimento solo campi essenziali
- ✅ Migrazione dati automatica
- ✅ Backup completo prima delle modifiche

**Struttura finale:**
```sql
jta_pharmas:
- id, email, status, phone_number
- business_name, nice_name, city, address
- latlng, description, working_info, prompt
- img_avatar, img_cover, img_bot
- created_at, updated_at
```

## 🎨 Interfaccia Utente

### Design System
- **Framework**: Bootstrap 5
- **Icone**: Bootstrap Icons
- **Temi**: Light/Dark mode support
- **Responsive**: Mobile-first design

### Componenti Principali
- **Navbar** - Navigazione principale
- **Sidebar** - Menu laterale con funzioni
- **Modals** - Form per creazione/modifica
- **Tables** - Liste con filtri e ricerca
- **Alerts** - Messaggi di feedback
- **Loading** - Indicatori di caricamento

### JavaScript
- **Framework**: Vanilla JS con moduli
- **API Client**: Fetch con gestione errori
- **UI Components**: Sistema modulare
- **Event Handling**: Delegazione eventi
- **Form Validation**: Client e server-side

## 🔧 Configurazione

### File di Configurazione
- `config/database.php` - Configurazione database
- `config/development.php` - Configurazione sviluppo
- `includes/functions.php` - Funzioni utility
- `includes/auth_middleware.php` - Middleware autenticazione

### Variabili d'Ambiente
```php
// Database
DB_HOST, DB_NAME, DB_USER, DB_PASS

// Sviluppo
DEBUG_MODE, LOG_LEVEL, ERROR_REPORTING

// Sicurezza
SESSION_TIMEOUT, CSRF_TOKEN_LIFETIME
```

## 🚀 Installazione

### Requisiti
- PHP 7.4+
- MySQL 5.7+
- Apache/Nginx
- Composer (opzionale)

### Setup
1. **Clona il repository**
   ```bash
   git clone [repository-url]
   cd assistente_farmacia_panel
   ```

2. **Configura il database**
   ```bash
   # Crea il database
   mysql -u root -p -e "CREATE DATABASE assistente_farmacia;"
   
   # Importa la struttura
   mysql -u root -p assistente_farmacia < database/schema.sql
   ```

3. **Configura le credenziali**
   ```bash
   # Modifica config/database.php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'assistente_farmacia');
   define('DB_USER', 'your_username');
   define('DB_PASS', 'your_password');
   ```

4. **Crea l'amministratore**
   ```bash
   php create_admin.php
   ```

5. **Avvia il server**
   ```bash
   php -S localhost:8000
   ```

## 🧪 Testing

### Test Funzionali
```bash
# Test sintassi PHP
find . -name "*.php" -exec php -l {} \;

# Test connessione database
php -r "require 'config/database.php'; echo 'Database OK';"

# Test API endpoints
curl -X POST http://localhost:8000/api/users/list.php
```

### Test Sicurezza
- ✅ Controllo accessi non autorizzati
- ✅ Validazione input
- ✅ Protezione CSRF
- ✅ Sanitizzazione output
- ✅ Log accessi non autorizzati

## 📊 Monitoraggio

### Log Files
- `logs/development.log` - Log sviluppo
- `logs/error.log` - Log errori
- `logs/access.log` - Log accessi

### Metriche
- Accessi utenti
- Operazioni CRUD
- Errori sistema
- Performance query

## 🔒 Sicurezza

### Implementazioni
- ✅ Autenticazione basata su sessioni
- ✅ Controllo accessi basato su ruoli
- ✅ Protezione CSRF
- ✅ Sanitizzazione input/output
- ✅ Hash password sicuro
- ✅ Log accessi non autorizzati
- ✅ Timeout sessioni
- ✅ Validazione lato server

### Best Practices
- Controlli accesso prima di qualsiasi output
- Validazione sempre lato server
- Logging di tutte le operazioni critiche
- Backup regolari del database
- Aggiornamenti di sicurezza

## 🛠️ Manutenzione

### Backup
- **Database**: Backup automatico giornaliero
- **File**: Backup prima di modifiche importanti
- **Versioning**: Git per controllo versioni

### Pulizia
- **Log**: Rotazione automatica
- **Sessioni**: Pulizia sessioni scadute
- **Cache**: Pulizia cache temporanea

### Aggiornamenti
- **Sicurezza**: Patch di sicurezza immediate
- **Funzionalità**: Aggiornamenti pianificati
- **Database**: Migrazioni versionate

## 📞 Supporto

### Documentazione
- **README.md** - Documentazione generale
- **docs/** - Documentazione specifica
- **Commenti codice** - Documentazione inline

### Contatti
- **Sviluppatore**: [Nome Sviluppatore]
- **Email**: [email@example.com]
- **Repository**: [URL Repository]

## 📝 Changelog

### Versione 2.0.0 (2025-07-29)
- ✅ Sistema di controllo accessi completo
- ✅ Gestione utenti CRUD completa
- ✅ Gestione farmacie CRUD completa
- ✅ Ottimizzazione database
- ✅ Interfaccia utente moderna
- ✅ API RESTful complete
- ✅ Sistema di logging avanzato

### Versione 1.0.0 (Data precedente)
- ✅ Funzionalità base
- ✅ Autenticazione semplice
- ✅ Gestione utenti base

---

**Assistente Farmacia Panel** - Sistema di gestione completo per farmacie 