# Funzionalità: Accesso come Farmacia

## Panoramica
Questa funzionalità permette agli amministratori di accedere alla piattaforma con l'account di una farmacia specifica, operando a nome della farmacia e visualizzando tutte le sezioni dedicate alla farmacia.

## Come Utilizzare

### 1. Accesso come Farmacia
1. Accedi come amministratore
2. Vai alla sezione "Gestione Utenti"
3. Trova l'utente farmacista per la farmacia desiderata
4. Clicca sul pulsante verde "Accedi come" (icona di login)
5. Conferma l'azione
6. Verrai reindirizzato alla dashboard della farmacia

### 2. Indicatori Visivi
Quando sei in modalità "accesso come farmacia", vedrai:
- **Sidebar**: Un indicatore blu che mostra "Accesso come Farmacia" con il nome della farmacia
- **Dashboard**: Un alert informativo che indica la modalità di accesso
- **Menu**: Tutte le voci del menu farmacista sono ora disponibili

### 3. Operazioni Disponibili
In modalità "accesso come farmacia" puoi:
- Visualizzare la dashboard della farmacia
- Gestire clienti
- Modificare orari di apertura
- Gestire prodotti e promozioni
- Visualizzare richieste
- Gestire il profilo della farmacia
- Utilizzare WhatsApp

### 4. Tornare alla Lista Utenti
Per tornare alla lista utenti:
1. Clicca sul pulsante "Torna Admin" nella sidebar (icona freccia a sinistra)
2. Conferma l'azione
3. Verrai reindirizzato alla lista utenti

## Sicurezza
- Solo gli amministratori possono utilizzare questa funzionalità
- L'accesso è tracciato nei log del sistema
- È sempre possibile tornare all'account amministratore
- I dati dell'admin originale sono preservati durante l'accesso come farmacia

## File Modificati
- `utenti.php` - Aggiunto pulsante "Accedi come" per farmacisti
- `includes/sidebar.php` - Aggiunto indicatore e pulsante "Torna Admin"
- `dashboard.php` - Aggiunto alert informativo
- `assets/js/utenti.js` - Aggiornate funzioni JavaScript
- `assets/js/main.js` - Aggiunta funzione returnToAdmin globale

## API Utilizzate
- `api/users/login-as.php` - Per accedere come farmacia
- `api/users/return-admin.php` - Per tornare all'account admin

## Note Tecniche
- La funzionalità utilizza il flag `$_SESSION['login_as']` per tracciare la modalità
- I dati dell'admin originale sono salvati in `$_SESSION['original_admin_*']`
- Il menu si adatta automaticamente in base al ruolo dell'utente impersonificato 