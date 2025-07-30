#!/bin/bash

# Script per avviare il server di sviluppo locale
# Assistente Farmacia Panel

# Colori per output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
RED='\033[0;31m'
CYAN='\033[0;36m'
NC='\033[0m' # No Color

# Configurazione
HOST="localhost"
PORT="8000"
DOCUMENT_ROOT=$(pwd)

# Header
echo -e "${CYAN}╔══════════════════════════════════════════════════════════════╗${NC}"
echo -e "${CYAN}║                    ASSISTENTE FARMACIA PANEL                ║${NC}"
echo -e "${CYAN}║                     Server di Sviluppo                      ║${NC}"
echo -e "${CYAN}╚══════════════════════════════════════════════════════════════╝${NC}"
echo ""

# Verifica PHP
if ! command -v php &> /dev/null; then
    echo -e "${RED}❌ PHP non è installato o non è nel PATH${NC}"
    echo -e "${YELLOW}Installa PHP per continuare${NC}"
    exit 1
fi

# Informazioni
echo -e "${BLUE}📋 Informazioni Sistema:${NC}"
echo -e "${YELLOW}   • PHP Version:${NC} $(php -v | head -n1)"
echo -e "${YELLOW}   • Document Root:${NC} $DOCUMENT_ROOT"
echo -e "${YELLOW}   • Host:${NC} $HOST"
echo -e "${YELLOW}   • Porta:${NC} $PORT"
echo ""

# Verifica porta
if lsof -Pi :$PORT -sTCP:LISTEN -t >/dev/null ; then
    echo -e "${RED}⚠️  La porta $PORT è già in uso${NC}"
    echo -e "${YELLOW}Termina il processo sulla porta $PORT o cambia porta${NC}"
    echo ""
fi

# URL di accesso
URL="http://$HOST:$PORT"
echo -e "${BLUE}🌐 URL di Accesso:${NC}"
echo -e "${YELLOW}   • Dashboard:${NC} $URL/dashboard.html"
echo -e "${YELLOW}   • Login:${NC} $URL/login.php"
echo -e "${YELLOW}   • Prodotti:${NC} $URL/prodotti.html"
echo -e "${YELLOW}   • Prenotazioni:${NC} $URL/prenotazioni.html"
echo ""

# Istruzioni
echo -e "${BLUE}📖 Istruzioni:${NC}"
echo -e "${CYAN}   1. Apri il browser e vai su:${NC} $URL"
echo -e "${CYAN}   2. Premi CTRL+C per fermare il server${NC}"
echo -e "${CYAN}   3. Il server si aggiorna automaticamente quando modifichi i file${NC}"
echo ""

# Avvisi
echo -e "${BLUE}⚠️  Avvisi:${NC}"
echo -e "${YELLOW}   • Questo è un server di sviluppo, NON usare in produzione${NC}"
echo -e "${YELLOW}   • Assicurati che la porta $PORT sia libera${NC}"
echo -e "${YELLOW}   • Per problemi di CORS, usa il browser in modalità sviluppatore${NC}"
echo ""

# Separatore
echo -e "${CYAN}══════════════════════════════════════════════════════════════════${NC}"
echo -e "${GREEN}🚀 Avvio server in corso...${NC}"
echo -e "${CYAN}══════════════════════════════════════════════════════════════════${NC}"
echo ""

# Avvia il server
echo -e "${GREEN}Server avviato! Apri il browser su:${NC} $URL"
echo -e "${YELLOW}Premi CTRL+C per fermare il server${NC}"
echo ""

# Comando PHP
php -S $HOST:$PORT -t $DOCUMENT_ROOT 