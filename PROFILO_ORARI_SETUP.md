# Configurazione Profilo e Orari Farmacia

## Panoramica

Sono state implementate le sezioni **Profilo** e **Orari** per permettere alle farmacie di gestire i propri dati anagrafici e gli orari di apertura.

## Sezione Profilo

### Funzionalità
- **Modifica dati anagrafici** della farmacia
- **Campi modificabili**:
  - Email
  - Telefono
  - Ragione sociale
  - Nome farmacia
  - Città
  - Indirizzo
  - Posizione (lat/lng)
  - Descrizione

### Sicurezza
- Solo i **farmacisti** possono modificare la propria farmacia
- **Validazione CSRF** per tutti i form
- **Validazione lato client e server**
- **Sanitizzazione** di tutti gli input

### File Implementati
- `profilo.php` - Pagina principale
- `assets/js/profile.js` - JavaScript per gestione form
- `api/pharmacies/update-profile.php` - API per aggiornamento

## Sezione Orari

### Funzionalità
- **Gestione orari di apertura** per tutti i giorni della settimana
- **Orari mattina e pomeriggio** separati
- **Opzione "Chiuso"** per ogni giorno
- **Giorno di turno** configurabile
- **Validazione orari** (apertura prima della chiusura)

### Struttura Orari
Ogni giorno ha:
- **Mattina**: Orario apertura e chiusura
- **Pomeriggio**: Orario apertura e chiusura
- **Chiuso**: Checkbox per marcare il giorno come chiuso

### File Implementati
- `orari.php` - Pagina principale
- `assets/js/orari.js` - JavaScript per gestione form
- `api/pharmacies/update-hours.php` - API per aggiornamento orari
- `api/pharmacies/update-turno.php` - API per aggiornamento turno

## Struttura Database

### Tabella `jta_pharmas`
Campi utilizzati:
- `email` - Email farmacia
- `phone_number` - Telefono
- `business_name` - Ragione sociale
- `nice_name` - Nome farmacia
- `city` - Città
- `address` - Indirizzo
- `latlng` - Coordinate GPS
- `description` - Descrizione
- `working_info` - Orari (JSON)
- `turno_giorno` - Giorno di turno

### Formato Orari (JSON)
```json
{
  "lun": {
    "closed": false,
    "morning_open": "08:00",
    "morning_close": "12:00",
    "afternoon_open": "15:00",
    "afternoon_close": "19:00"
  },
  "mar": {
    "closed": true
  }
}
```

## Utilizzo

### Profilo
1. Vai su **"Profilo"** nel menu laterale
2. Modifica i campi desiderati
3. Clicca **"Salva Modifiche"**
4. I dati vengono aggiornati immediatamente

### Orari
1. Vai su **"Modifica Orari"** nel menu laterale
2. Imposta gli orari per ogni giorno
3. Usa la checkbox **"Chiuso"** per i giorni di chiusura
4. Clicca **"Salva Orari"**
5. Imposta il **giorno di turno** se necessario

## Validazioni

### Profilo
- **Email**: Formato valido (se fornita)
- **Telefono**: Formato valido (se fornito)
- **Campi obbligatori**: business_name, nice_name, city, address
- **Nome unico**: Controllo duplicati nice_name

### Orari
- **Orari obbligatori**: Se il giorno non è chiuso
- **Apertura < Chiusura**: Validazione logica orari
- **Formato orari**: HH:MM valido
- **Giorno turno**: Valore tra quelli consentiti

## Sicurezza

### Autenticazione
- Solo utenti autenticati
- Solo farmacisti per le proprie farmacie
- Controllo sessione per ogni richiesta

### Validazione
- **CSRF Token** per tutti i form
- **Sanitizzazione** input lato server
- **Validazione** lato client e server
- **Escape HTML** per output

### Logging
- **Log attività** per ogni modifica
- **Tracciamento** delle operazioni
- **Gestione errori** completa

## Note Tecniche

### JavaScript
- **Validazione real-time** dei form
- **Gestione stati** di caricamento
- **Feedback utente** immediato
- **Gestione errori** completa

### API
- **RESTful** design
- **JSON** response
- **HTTP status codes** appropriati
- **Error handling** robusto

### UI/UX
- **Design responsive** Bootstrap
- **Feedback visivo** per validazioni
- **Loading states** per operazioni
- **Alert messages** per feedback 