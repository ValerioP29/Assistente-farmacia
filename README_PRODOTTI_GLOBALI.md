# Gestione Prodotti Globali

## Panoramica

Il modulo **Gestione Prodotti Globali** permette agli amministratori di gestire l'anagrafica completa dei prodotti farmaceutici che saranno disponibili per tutte le farmacie del sistema.

## Caratteristiche Principali

### 🔧 Funzionalità CRUD Complete
- **Creazione**: Aggiunta di nuovi prodotti con tutti i dettagli
- **Lettura**: Visualizzazione lista prodotti con paginazione e filtri
- **Aggiornamento**: Modifica di prodotti esistenti
- **Eliminazione**: Rimozione sicura di prodotti

### 📊 Gestione Avanzata
- **Paginazione**: Gestione efficiente di grandi quantità di dati
- **Filtri**: Ricerca per nome, categoria, brand, stato
- **Ordinamento**: Ordinamento alfabetico per nome
- **Stato**: Attivazione/disattivazione prodotti

### 📁 Import/Export Massivo
- **Export CSV**: Esportazione completa con filtri applicati
- **Import CSV**: Importazione massiva con validazione
- **Template**: Download template per import corretto
- **Aggiornamento**: Opzione per aggiornare prodotti esistenti

### 🖼️ Gestione Immagini
- **Upload**: Caricamento immagini prodotto
- **Preview**: Anteprima immediata
- **Validazione**: Controllo formato e dimensione
- **Storage**: Salvataggio sicuro in directory uploads

## Struttura Database

### Tabella `jta_global_prods`

| Campo | Tipo | Descrizione |
|-------|------|-------------|
| `id` | INT | Chiave primaria auto-increment |
| `sku` | VARCHAR(50) | Codice prodotto univoco |
| `name` | VARCHAR(255) | Nome prodotto |
| `description` | TEXT | Descrizione dettagliata |
| `image` | VARCHAR(255) | Percorso immagine |
| `category` | VARCHAR(100) | Categoria prodotto |
| `brand` | VARCHAR(100) | Marchio produttore |
| `active_ingredient` | VARCHAR(255) | Principio attivo |
| `dosage_form` | VARCHAR(50) | Forma farmaceutica |
| `strength` | VARCHAR(50) | Dosaggio/concentrazione |
| `package_size` | VARCHAR(50) | Dimensione confezione |
| `requires_prescription` | TINYINT(1) | Richiede ricetta (0/1) |
| `is_active` | TINYINT(1) | Prodotto attivo (0/1) |
| `created_at` | TIMESTAMP | Data creazione |
| `updated_at` | TIMESTAMP | Data ultimo aggiornamento |

## API Endpoints

### 📋 Lista Prodotti
```
GET /api/products/list.php
```
**Parametri:**
- `page`: Numero pagina (default: 1)
- `limit`: Prodotti per pagina (default: 20)
- `search`: Ricerca testuale
- `category`: Filtro categoria
- `brand`: Filtro brand
- `status`: Filtro stato (0/1)

### 📄 Dettaglio Prodotto
```
GET /api/products/get.php?id={id}
```

### ➕ Aggiungi Prodotto
```
POST /api/products/add.php
```
**Campi obbligatori:** `sku`, `name`

### ✏️ Aggiorna Prodotto
```
POST /api/products/update.php
```
**Campi obbligatori:** `id`, `sku`, `name`

### 🗑️ Elimina Prodotto
```
POST /api/products/delete.php
```
**Body JSON:** `{"id": product_id}`

### 🔍 Filtri Disponibili
```
GET /api/products/filters.php
```
Restituisce categorie e brand unici

### 📤 Export CSV
```
GET /api/products/export.php
```
**Parametri:** Stessi filtri della lista

### 📥 Import CSV
```
POST /api/products/import.php
```
**File:** CSV con intestazioni
**Opzioni:** Aggiorna prodotti esistenti

### 📋 Template Import
```
GET /api/products/template.php
```
Download template CSV con esempi

## Interfaccia Utente

### 🎨 Design Responsive
- **Desktop**: Layout completo con sidebar
- **Tablet**: Adattamento automatico
- **Mobile**: Interfaccia ottimizzata

### 🎯 Componenti UI
- **Tabella**: Visualizzazione dati con hover effects
- **Modal**: Form per creazione/modifica
- **Filtri**: Ricerca avanzata con dropdown
- **Paginazione**: Navigazione tra pagine
- **Alert**: Messaggi di feedback

### 🎨 Stili Personalizzati
- **Tema**: Coerente con il design system
- **Colori**: Palette aziendale
- **Animazioni**: Transizioni fluide
- **Icone**: Font Awesome integration

## Sicurezza

### 🔐 Controlli Accesso
- **Ruolo**: Solo amministratori
- **Session**: Verifica autenticazione
- **CSRF**: Protezione cross-site request forgery

### ✅ Validazione Input
- **SKU**: Unicità e formato
- **File**: Tipo e dimensione
- **Dati**: Sanitizzazione completa

### 📝 Logging
- **Attività**: Log di tutte le operazioni
- **Dettagli**: Informazioni complete
- **Audit**: Tracciabilità completa

## Utilizzo

### 👤 Accesso
1. Effettua login come amministratore
2. Accedi alla voce "Gestione Prodotti" nel menu
3. Visualizza la lista prodotti esistenti

### ➕ Aggiunta Prodotto
1. Clicca "Nuovo Prodotto"
2. Compila i campi obbligatori (SKU, Nome)
3. Aggiungi dettagli opzionali
4. Carica immagine se necessario
5. Salva il prodotto

### 📤 Export Dati
1. Applica filtri se necessario
2. Clicca "Esporta"
3. Scarica il file CSV

### 📥 Import Dati
1. Clicca "Importa"
2. Scarica il template
3. Compila il file CSV
4. Carica il file
5. Scegli se aggiornare prodotti esistenti
6. Conferma l'import

## Manutenzione

### 🗄️ Database
- **Backup**: Backup regolare della tabella
- **Indici**: Ottimizzazione query
- **Pulizia**: Rimozione prodotti inattivi

### 📁 File System
- **Uploads**: Gestione directory immagini
- **Pulizia**: Rimozione file orfani
- **Permessi**: Controllo accessi directory

### 🔄 Aggiornamenti
- **Versioning**: Controllo versioni
- **Migration**: Aggiornamenti schema
- **Rollback**: Procedure di ripristino

## Troubleshooting

### ❌ Problemi Comuni

**Errore Upload Immagine**
- Verifica formato file (JPG, PNG, GIF)
- Controlla dimensione (max 5MB)
- Verifica permessi directory uploads

**Errore Import CSV**
- Controlla formato file (separatore ;)
- Verifica intestazioni colonne
- Controlla codifica UTF-8

**SKU Duplicato**
- Verifica unicità SKU
- Controlla prodotti inattivi
- Usa SKU univoci

### 🔧 Soluzioni

**Reset Filtri**
- Clicca "Pulisci Filtri"
- Ricarica la pagina

**Ripristino Dati**
- Usa backup database
- Importa dati da export precedente

**Pulizia Cache**
- Svuota cache browser
- Ricarica pagina

## Sviluppo

### 🛠️ Tecnologie
- **Backend**: PHP 7.4+
- **Database**: MySQL 5.7+
- **Frontend**: HTML5, CSS3, JavaScript
- **Framework**: Bootstrap 5
- **Icone**: Font Awesome

### 📁 Struttura File
```
prodotti_globali.php          # Pagina principale
assets/js/prodotti_globali.js # JavaScript
assets/css/prodotti_globali.css # Stili
api/products/                 # API endpoints
├── list.php                  # Lista prodotti
├── get.php                   # Dettaglio
├── add.php                   # Aggiungi
├── update.php                # Aggiorna
├── delete.php                # Elimina
├── filters.php               # Filtri
├── export.php                # Export
├── import.php                # Import
└── template.php              # Template
```

### 🔄 Estensioni Future
- **API REST**: Endpoint standardizzati
- **Webhook**: Notifiche automatiche
- **Integrazione**: Sistemi esterni
- **Analytics**: Statistiche utilizzo
- **Backup**: Automatizzazione backup 