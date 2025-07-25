// JavaScript
document.addEventListener('DOMContentLoaded', () => {
  crea_tabella();

  async function crea_tabella() {
    try {
      const response = await fetch('../data/prodotti.json');
      if (!response.ok) throw new Error('Errore HTTP: ' + response.status);
      const datiGlobali = await response.json();

      const contenitore = document.getElementById("contenitoreCard");
      contenitore.innerHTML = ''; // pulisci contenitore

      datiGlobali.forEach((item) => {
        const col = document.createElement("div");
        col.className = "col";

      col.innerHTML = `
        <div class="card h-100 shadow-sm">
            <img 
              class="card-img-top img-fluid" 
              src="${item.img ? '../app/'+item.img : './images/default.png'}" 
              alt="${item.nome}"
              onerror="this.onerror=null; this.src='./images/default.png';"
              style="cursor: pointer;"
              data-bs-toggle="modal" 
              data-bs-target="#imgModal"
              onclick="showImageInModal(this)"
            >

            <div class="card-body d-flex flex-column">
              <h5 class="card-title">${item.nome}</h5>
              <p class="text-muted mb-1">Codice: ${item.id}</p>
              <p class="card-text flex-grow-1">${item.descrizione}</p>
              ${checkPrezzo(item.prezzo, item.prezzo_scontato)}

              <div class="row mt-4">
                <div class="col-6">
                  <a href="farmacia/modifica_prodotto/${item.id}" class="btn w-100 btn-sm btn-warning">Modifica</a>
                </div>
                <div class="col-6">
                  <button class="btn w-100 btn-sm btn-danger" onclick="confermaEliminazione('${item.id}')">Elimina</button>
                </div>
              </div>
            </div>
            
        </div>
        `;

        contenitore.appendChild(col);
      });
    } catch (error) {
      console.error("Errore nel caricamento dei dati:", error);
    }
  }

  function checkPrezzo(prezzo, prezzoScontato) {
    if (prezzoScontato && prezzoScontato !== "-") {
      return `
        <div class="mt-2">
          <span class="text-danger fw-bold me-2 h4">${prezzoScontato.toFixed(2)}€</span>
          <span class="text-muted text-decoration-line-through h4"><s>${prezzo.toFixed(2)}€</s></span>
        </div>`;
    } else {
      return `<div class="mt-2"><span class="fw-bold h4">${prezzo.toFixed(2)}€</span></div>`;
    }
  }

  // Funzione per confermare l'eliminazione
  window.confermaEliminazione = function(id) {
    if (confirm("Sei sicuro di voler eliminare il prodotto?")) {
      console.log("Elimina prodotto con ID:", id);
      // qui puoi aggiungere la logica di eliminazione (AJAX o fetch)
    }
  };
});
