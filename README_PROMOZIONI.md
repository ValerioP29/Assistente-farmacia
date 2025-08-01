# Gestione Promozioni

## Panoramica

Il modulo **Gestione Promozioni** permette ai farmacisti di gestire le promozioni sui prodotti della propria farmacia. Utilizza un layout a schede moderno e intuitivo per una migliore esperienza utente.

## Caratteristiche Principali

### 🎨 Layout a Schede
- **Visualizzazione moderna**: Layout a schede invece della tradizionale tabella
- **Animazioni fluide**: Effetti hover e transizioni eleganti
- **Responsive design**: Ottimizzato per dispositivi mobili e desktop
- **Badge di stato**: Indicatori visivi per lo stato delle promozioni

### 📊 Dashboard Statistiche
- **Promozioni Attive**: Numero di promozioni attualmente in corso
- **In Arrivo**: Promozioni programmate per il futuro
- **In Scadenza**: Promozioni che scadono entro 7 giorni
- **Sconto Medio**: Percentuale media di sconto delle promozioni attive

### 🔍 Filtri Avanzati
- **Ricerca testuale**: Cerca per nome prodotto, SKU, descrizione
- **Filtro per stato**: Attive, Inattive, Scadute, In arrivo
- **Filtro per categoria**: Tutte le categorie disponibili
- **Filtro per sconto**: Range di percentuali di sconto (0-10%, 10-25%, 25-50%, 50%+)

### ⏰ Gestione Temporale
- **Date di inizio e fine**: Controllo preciso del periodo promozionale
- **Progress bar**: Indicatore visivo del tempo rimanente
- **Stati automatici**: Rilevamento automatico dello stato basato sulle date

### 💰 Gestione Prezzi
- **Prezzo originale**: Prezzo di listino del prodotto
- **Prezzo scontato**: Prezzo promozionale
- **Calcolo automatico**: Percentuale di sconto calcolata automaticamente
- **Validazione**: Controlli sui prezzi e sulle date

## Struttura Database

### Tabella `jta_pharma_prods`

| Campo | Tipo | Descrizione |
|-------|------|-------------|
| `id` | INT | Chiave primaria auto-increment |
| `pharma_id` | INT | ID della farmacia |
| `product_id` | INT | ID del prodotto globale associato |
| `price` | DECIMAL(10,2) | Prezzo originale |
| `sale_price` | DECIMAL(10,2) | Prezzo scontato |
| `is_on_sale` | TINYINT(1) | Se il prodotto è in promozione |
| `sale_start_date` | DATE | Data inizio promozione |
| `sale_end_date` | DATE | Data fine promozione |
| `is_active` | TINYINT(1) | Se il prodotto è attivo |

## Funzionalità CRUD

### ✅ Creazione Promozione
- Selezione prodotto dal catalogo farmacia
- Impostazione prezzo scontato
- Definizione periodo promozionale
- Attivazione/disattivazione immediata

### 📖 Visualizzazione
- Layout a schede con informazioni complete
- Immagine prodotto con fallback
- Prezzi originali e scontati
- Date e stato promozione
- Progress bar per tempo rimanente

### ✏️ Modifica Promozione
- Aggiornamento prezzo scontato
- Modifica date promozione
- Toggle attivazione/disattivazione
- Validazione automatica

### 🗑️ Eliminazione Promozione
- Conferma prima dell'eliminazione
- Rimozione sicura dal database
- Log delle attività

## Import/Export

### 📤 Export
- **Formato CSV**: Esportazione con filtri applicati
- **Colonne complete**: Tutte le informazioni promozionali
- **Formattazione italiana**: Date e numeri nel formato locale
- **Nome file dinamico**: Timestamp per evitare conflitti

### 📥 Import
- **Template disponibile**: Download template CSV
- **Validazione dati**: Controlli sui formati e valori
- **Aggiornamento esistente**: Opzione per aggiornare promozioni esistenti
- **Gestione errori**: Report dettagliato degli errori

## Stati Promozione

### 🟢 Attiva
- `is_on_sale = 1`
- `DATE(sale_start_date) <= CURDATE()`
- `DATE(sale_end_date) >= CURDATE()`

### 🟡 In Arrivo
- `is_on_sale = 1`
- `DATE(sale_start_date) > CURDATE()`

### 🔴 Scaduta
- `is_on_sale = 1`
- `DATE(sale_end_date) < CURDATE()`

### ⚫ Inattiva
- `is_on_sale = 0`

## API Endpoints

### 📊 Statistiche
- `GET api/pharma-products/stats.php`
- Restituisce conteggi e medie delle promozioni

### 📋 Lista Promozioni
- `GET api/pharma-products/list.php`
- Supporta filtri avanzati per promozioni

### ➕ Creazione/Aggiornamento
- `POST api/pharma-products/add.php`
- Gestisce creazione e modifica promozioni

### 🔄 Aggiornamento Rapido
- `POST api/pharma-products/update.php`
- Aggiornamento specifico campi promozione

### 📤 Export
- `GET api/pharma-products/export-promotions.php`
- Esportazione CSV con filtri

### 📄 Template
- `GET api/pharma-products/template.php`
- Download template per import

## Interfaccia Utente

### 🎨 Design System
- **Colori**: Gradienti moderni per header e badge
- **Icone**: Font Awesome per coerenza visiva
- **Tipografia**: Gerarchia chiara dei testi
- **Spaziature**: Layout arioso e leggibile

### 📱 Responsive
- **Mobile-first**: Ottimizzato per dispositivi mobili
- **Breakpoints**: Adattamento automatico alle dimensioni schermo
- **Touch-friendly**: Pulsanti e interazioni ottimizzate

### ⚡ Performance
- **Lazy loading**: Caricamento progressivo delle schede
- **Debounce**: Ottimizzazione ricerca in tempo reale
- **Caching**: Memorizzazione dati per ridurre chiamate API

## Sicurezza

### 🔐 Autenticazione
- Verifica ruolo farmacista
- Controllo appartenenza farmacia
- Session management sicuro

### 🛡️ Validazione
- Sanitizzazione input utente
- Validazione date e prezzi
- Controlli di integrità dati

### 📝 Logging
- Tracciamento modifiche promozioni
- Log attività utente
- Audit trail completo

## Utilizzo

### 1. Accesso
- Navigare su "Gestione Promozioni" dalla sidebar
- Verifica automatica dei permessi

### 2. Creazione Promozione
- Cliccare "Nuova Promozione"
- Selezionare prodotto dal dropdown
- Impostare prezzo scontato e date
- Salvare la promozione

### 3. Gestione Promozioni
- Utilizzare filtri per trovare promozioni
- Modificare/eliminare tramite azioni sulle schede
- Monitorare statistiche in tempo reale

### 4. Export/Import
- Esportare promozioni con filtri applicati
- Importare tramite CSV con template fornito
- Verificare risultati dell'import

## Note Tecniche

### 🗄️ Database
- Utilizza la tabella esistente `jta_pharma_prods`
- JOIN con `jta_global_prods` per dati completi
- Indici ottimizzati per performance

### 🔧 Tecnologie
- **Frontend**: HTML5, CSS3, JavaScript ES6+
- **Backend**: PHP 7.4+, MySQL 5.7+
- **Framework**: Bootstrap 5, Font Awesome

### 📊 Performance
- Query ottimizzate con JOIN
- Paginazione lato server
- Caching intelligente
- Compressione risorse

## Manutenzione

### 🔄 Aggiornamenti
- Controllo regolare stati promozioni
- Pulizia promozioni scadute
- Backup dati promozionali

### 📈 Monitoraggio
- Log errori e performance
- Statistiche utilizzo
- Feedback utenti

### 🛠️ Troubleshooting
- Verifica connessione database
- Controllo permessi file
- Validazione configurazione 