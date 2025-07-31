# Sistema di Gestione Immagini Prodotti

## Panoramica

Il sistema di gestione immagini per i prodotti farmaceutici fornisce funzionalità complete per la gestione, ottimizzazione e organizzazione delle immagini dei prodotti.

## Caratteristiche Principali

### 🎨 Generazione Automatica
- **Placeholder personalizzati** con nome prodotto, SKU e categoria
- **Icone farmaceutiche** integrate nelle immagini
- **Categorie specifiche** con colori e stili dedicati
- **Fallback automatico** per prodotti senza immagine

### 📁 Organizzazione Filesystem
- **Directory per categoria** per organizzazione logica
- **Nomi file univoci** con timestamp per evitare conflitti
- **Struttura gerarchica** per facile manutenzione
- **Permessi sicuri** per protezione file

### 🖼️ Ottimizzazione Immagini
- **Ridimensionamento automatico** (max 800x800px)
- **Compressione intelligente** (qualità 85% per JPEG)
- **Mantenimento proporzioni** senza distorsioni
- **Supporto trasparenza** per PNG
- **Generazione thumbnail** (150x150px)

### 🔧 Gestione Avanzata
- **Validazione file** (tipo, dimensione, formato)
- **Eliminazione sicura** con pulizia filesystem
- **Fallback font** per compatibilità cross-platform
- **Gestione errori** robusta

## Struttura Directory

```
uploads/products/
├── antidolorifici/
├── antinfiammatori/
├── integratori/
├── antibiotici/
├── cardiovascolari/
├── dermatologici/
├── gastroenterologici/
├── respiratori/
├── vitamine/
└── generico/

assets/images/
├── default-product.png
└── default-product-thumb.png

assets/fonts/
└── arial.ttf (opzionale)
```

## API e Funzioni

### 📋 Classe ProductImageManager

```php
$manager = new ProductImageManager();

// Genera placeholder
$imagePath = $manager->generatePlaceholderImage($name, $category, $sku);

// Processa upload
$imagePath = $manager->processUploadedImage($file, $category);

// Elimina immagine
$manager->deleteImage($imagePath);

// Genera thumbnail
$thumbnailPath = $manager->generateThumbnail($imagePath);
```

### 🛠️ Funzioni Helper

```php
// Genera placeholder
$imagePath = generateProductPlaceholder($name, $category, $sku);

// Processa upload
$imagePath = processProductImage($file, $category);

// Elimina immagine
deleteProductImage($imagePath);

// Ottieni URL
$url = getProductImageUrl($imagePath);
$thumbUrl = getProductThumbnailUrl($imagePath);
```

## Configurazione

### ⚙️ Parametri Personalizzabili

```php
class ProductImageManager {
    private $maxWidth = 800;        // Larghezza massima
    private $maxHeight = 800;       // Altezza massima
    private $quality = 85;          // Qualità JPEG
    private $maxFileSize = 5MB;     // Dimensione massima file
    private $allowedTypes = [...];  // Tipi file supportati
}
```

### 🎨 Categorie Supportate

- `antidolorifici` - Prodotti per il dolore
- `antinfiammatori` - Farmaci antinfiammatori
- `integratori` - Integratori alimentari
- `antibiotici` - Antibiotici
- `cardiovascolari` - Farmaci cardiovascolari
- `dermatologici` - Prodotti dermatologici
- `gastroenterologici` - Farmaci gastrointestinali
- `respiratori` - Farmaci respiratori
- `vitamine` - Vitamine e minerali
- `generico` - Categoria di default

## Utilizzo

### ➕ Aggiunta Prodotto con Immagine

```php
// Upload immagine
if (isset($_FILES['image'])) {
    $imagePath = processProductImage($_FILES['image'], $category);
} else {
    // Genera placeholder automatico
    $imagePath = generateProductPlaceholder($name, $category, $sku);
}

// Salva nel database
$data['image'] = $imagePath;
```

### ✏️ Aggiornamento Immagine

```php
// Elimina immagine precedente
deleteProductImage($existingImage);

// Processa nuova immagine
$newImagePath = processProductImage($_FILES['image'], $category);
```

### 🗑️ Eliminazione Prodotto

```php
// Elimina immagine automaticamente
deleteProductImage($product['image']);
```

## Frontend Integration

### 🎯 Visualizzazione Tabella

```javascript
// Mostra immagine prodotto
${product.image ? 
    `<img src="${product.image}" class="product-image" onerror="this.src='assets/images/default-product-thumb.png'">` : 
    '<div class="product-image-placeholder"><i class="fas fa-pills"></i></div>'
}
```

### 🖼️ Preview Modal

```javascript
// Preview immagine caricata
if (product.image) {
    document.getElementById('previewImg').src = product.image;
    document.getElementById('imagePreview').style.display = 'block';
}
```

## Sicurezza

### ✅ Validazioni

- **Tipo file**: Solo JPG, PNG, GIF
- **Dimensione**: Massimo 5MB
- **Contenuto**: Verifica integrità immagine
- **Percorso**: Sanitizzazione nomi file

### 🔒 Protezioni

- **Directory traversal**: Prevenzione attacchi
- **File upload**: Validazione rigorosa
- **Permessi**: Directory con permessi sicuri
- **Eliminazione**: Pulizia completa filesystem

## Performance

### ⚡ Ottimizzazioni

- **Ridimensionamento**: Immagini ottimizzate automaticamente
- **Thumbnail**: Generazione automatica per liste
- **Caching**: Browser caching per immagini statiche
- **Lazy loading**: Caricamento on-demand

### 📊 Metriche

- **Tempo generazione**: < 1 secondo per placeholder
- **Dimensione media**: ~50KB per immagine ottimizzata
- **Thumbnail**: ~15KB per preview
- **Memoria**: Gestione efficiente risorse

## Troubleshooting

### ❌ Problemi Comuni

**Errore generazione placeholder**
- Verifica estensione GD installata
- Controlla permessi directory uploads
- Verifica font disponibili

**Errore upload immagine**
- Controlla dimensione file (max 5MB)
- Verifica formato (JPG, PNG, GIF)
- Controlla permessi directory

**Immagine non visualizzata**
- Verifica percorso file nel database
- Controlla esistenza file filesystem
- Verifica permessi file

### 🔧 Soluzioni

**Reinstallazione font**
```bash
# Copia font di sistema
cp /usr/share/fonts/truetype/dejavu/DejaVuSans.ttf assets/fonts/arial.ttf
```

**Riparazione permessi**
```bash
# Imposta permessi corretti
chmod -R 755 uploads/products/
chown -R www-data:www-data uploads/products/
```

**Pulizia file orfani**
```php
// Script per pulizia
$orphanedFiles = findOrphanedImages();
foreach ($orphanedFiles as $file) {
    unlink($file);
}
```

## Estensioni Future

### 🚀 Funzionalità Avanzate

- **Watermark automatico** con logo aziendale
- **CDN integration** per distribuzione globale
- **WebP support** per compressione avanzata
- **AI tagging** per categorizzazione automatica
- **Batch processing** per import massivo
- **Image optimization** con algoritmi avanzati

### 🔄 Miglioramenti

- **Cache intelligente** per performance
- **Compressione progressiva** per caricamento veloce
- **Responsive images** per dispositivi mobili
- **Image analytics** per statistiche utilizzo
- **Backup automatico** delle immagini
- **Versioning** per storico modifiche

## Manutenzione

### 🧹 Pulizia Regolare

```bash
# Rimuovi file temporanei
find uploads/products/ -name "*.tmp" -delete

# Comprimi immagini vecchie
find uploads/products/ -mtime +30 -exec convert {} -quality 80 {} \;

# Backup immagini
tar -czf backup_images_$(date +%Y%m%d).tar.gz uploads/products/
```

### 📈 Monitoraggio

- **Spazio disco**: Controllo utilizzo storage
- **Performance**: Tempo generazione immagini
- **Errori**: Log errori upload/processamento
- **Utilizzo**: Statistiche accesso immagini

## Supporto

### 📞 Assistenza

Per problemi o richieste di supporto:
- Controlla i log di errore
- Verifica configurazione server
- Testa con immagini di esempio
- Consulta documentazione API

### 🔗 Risorse

- [Documentazione GD](https://www.php.net/manual/en/book.image.php)
- [Best Practices Images](https://web.dev/fast/#optimize-your-images)
- [Security Guidelines](https://owasp.org/www-community/vulnerabilities/Unrestricted_File_Upload) 