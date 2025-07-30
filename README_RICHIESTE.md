# Sezione Richieste - Assistente Farmacia Panel

## Panoramica

La sezione **Richieste** permette di gestire tutte le richieste che arrivano dalle farmacie. È un sistema completo per la gestione del workflow delle richieste con stati, filtri e statistiche.

## Funzionalità

### 🎯 Gestione Richieste
- **Visualizzazione** di tutte le richieste in una tabella responsive
- **Filtri avanzati** per stato, tipo di richiesta e ricerca testuale
- **Paginazione** per gestire grandi volumi di dati
- **Statistiche in tempo reale** con conteggi per ogni stato

### 📊 Stati delle Richieste
- **0 - In attesa** (Giallo): Richiesta ricevuta, in attesa di valutazione
- **1 - In lavorazione** (Blu): Richiesta approvata e in fase di implementazione
- **2 - Completata** (Verde): Richiesta completata con successo
- **3 - Rifiutata** (Rosso): Richiesta rifiutata
- **4 - Annullata** (Grigio): Richiesta annullata

### 🏷️ Tipi di Richiesta
- **Evento**: Organizzazione di eventi, workshop, campagne di sensibilizzazione
- **Servizio**: Implementazione di nuovi servizi (consegna a domicilio, consulenze)
- **Promozione**: Campagne promozionali, sconti, offerte speciali
- **Prenotazione**: Prenotazioni di consulenze, visite, servizi

### 🔧 Funzionalità Avanzate
- **Dettagli completi** di ogni richiesta con modal dedicato
- **Aggiornamento stato** con note e commenti
- **Timeline delle note** per tracciare la storia della richiesta
- **Log delle attività** per audit trail completo

## Struttura Database

### Tabella `jta_requests`
```sql
CREATE TABLE `jta_requests` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `request_type` enum('event','service','promos','reservation') NOT NULL,
  `user_id` int(11) UNSIGNED NOT NULL,
  `pharma_id` int(11) UNSIGNED NOT NULL,
  `message` mediumtext NOT NULL,
  `metadata` longtext NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `status` (`status`),
  KEY `pharma_id` (`pharma_id`),
  KEY `user_id` (`user_id`),
  KEY `request_type` (`request_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Campi Principali
- **`request_type`**: Tipo di richiesta (event, service, promos, reservation)
- **`user_id`**: ID dell'utente che ha fatto la richiesta
- **`pharma_id`**: ID della farmacia coinvolta
- **`message`**: Messaggio principale della richiesta
- **`metadata`**: Dati aggiuntivi in formato JSON (date, dettagli, note)
- **`status`**: Stato della richiesta (0-4)
- **`created_at`**: Data di creazione
- **`deleted_at`**: Soft delete per eliminazione logica

## API Endpoints

### 📋 Lista Richieste
```
GET /api/requests/list.php
```
**Parametri:**
- `page`: Numero pagina (default: 1)
- `limit`: Elementi per pagina (default: 20)
- `status`: Filtro per stato
- `request_type`: Filtro per tipo
- `pharma_id`: Filtro per farmacia
- `search`: Ricerca testuale

### 📊 Statistiche
```
GET /api/requests/stats.php
```
**Risposta:**
```json
{
  "success": true,
  "data": {
    "status_stats": {
      "pending": 5,
      "processing": 3,
      "completed": 12,
      "rejected": 2,
      "cancelled": 1,
      "total": 23
    },
    "type_stats": {
      "event": 8,
      "service": 6,
      "promos": 5,
      "reservation": 4
    },
    "top_pharmacies": [...]
  }
}
```

### 🔍 Dettagli Richiesta
```
GET /api/requests/get.php?id={request_id}
```

### ✏️ Aggiorna Stato
```
POST /api/requests/update-status.php
```
**Body:**
```json
{
  "request_id": 123,
  "status": 2,
  "note": "Richiesta completata con successo"
}
```

## File Principali

### Backend
- `richieste.php` - Pagina principale
- `api/requests/list.php` - API lista richieste
- `api/requests/stats.php` - API statistiche
- `api/requests/get.php` - API dettagli
- `api/requests/update-status.php` - API aggiorna stato

### Frontend
- `assets/js/richieste.js` - Logica JavaScript
- `assets/css/richieste.css` - Stili CSS

### Utility
- `test_requests.php` - Script per inserire dati di test

## Installazione e Setup

### 1. Installazione Database
La tabella viene creata automaticamente durante l'installazione del progetto tramite `install.php`.

### 2. Dati di Test
Per testare il sistema con dati di esempio:
```bash
php test_requests.php
```

### 3. Accesso
- Naviga su `richieste.php`
- Assicurati di essere autenticato come admin o farmacista
- La sezione è accessibile dal menu laterale

## Utilizzo

### Visualizzazione Richieste
1. Accedi alla sezione "Richieste" dal menu
2. Visualizza tutte le richieste nella tabella principale
3. Usa i filtri per trovare richieste specifiche
4. Controlla le statistiche in tempo reale

### Gestione Stato
1. Clicca sull'icona "Aggiorna stato" per una richiesta
2. Seleziona il nuovo stato dal dropdown
3. Aggiungi una nota opzionale
4. Salva le modifiche

### Visualizzazione Dettagli
1. Clicca sull'icona "Visualizza dettagli"
2. Esamina tutte le informazioni della richiesta
3. Visualizza la timeline delle note
4. Chiudi il modal quando hai finito

## Personalizzazione

### Aggiungere Nuovi Stati
1. Modifica le funzioni `getStatusLabel()` e `getStatusColor()` nei file API
2. Aggiorna il JavaScript con i nuovi stati
3. Aggiungi le opzioni nei dropdown HTML

### Aggiungere Nuovi Tipi
1. Modifica l'enum nel database
2. Aggiorna la funzione `getRequestTypeLabel()` nei file API
3. Aggiungi le opzioni nei filtri HTML

### Modificare i Colori
1. Modifica il file `assets/css/richieste.css`
2. Personalizza i colori dei badge e degli stati
3. Aggiorna le variabili CSS per coerenza

## Sicurezza

- **Autenticazione**: Tutte le API richiedono autenticazione
- **Validazione**: Input validati lato server
- **Autorizzazione**: Controlli sui ruoli utente
- **Logging**: Tutte le modifiche vengono registrate

## Troubleshooting

### Problemi Comuni

**Errore "Nessuna richiesta trovata"**
- Verifica che esistano dati nel database
- Controlla i filtri applicati
- Esegui `test_requests.php` per inserire dati di test

**Errore API**
- Controlla i log di errore PHP
- Verifica la connessione al database
- Controlla i permessi dei file

**Problemi di Stile**
- Verifica che `richieste.css` sia caricato
- Controlla la console del browser per errori CSS
- Assicurati che Bootstrap sia presente

## Supporto

Per problemi o richieste di supporto:
1. Controlla i log di errore
2. Verifica la documentazione
3. Controlla la console del browser
4. Verifica la connessione al database

---

**Versione**: 1.0.0  
**Data**: Febbraio 2024  
**Autore**: Assistente Farmacia Panel Team 