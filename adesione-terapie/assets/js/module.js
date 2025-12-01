(function () {
  const THERAPY_MODAL_SELECTOR = '#therapyModal';

  function waitForTherapyModal(timeoutMs = 3000) {
    return new Promise((resolve) => {
      const existing = document.querySelector(THERAPY_MODAL_SELECTOR);
      if (existing) return resolve(existing);

      const observer = new MutationObserver(() => {
        const modal = document.querySelector(THERAPY_MODAL_SELECTOR);
        if (modal) {
          observer.disconnect();
          resolve(modal);
        }
      });

      observer.observe(document.body || document.documentElement, {
        childList: true,
        subtree: true,
      });

      setTimeout(() => {
        observer.disconnect();
        resolve(null);
      }, timeoutMs);
    });
  }

  document.addEventListener('DOMContentLoaded', async () => {
    const routesBase = 'adesione-terapie/routes.php';
    const csrfToken = document
      .querySelector('meta[name="csrf-token"]')
      .getAttribute('content');

    const therapyModal = await waitForTherapyModal();
    const moduleRoot = therapyModal || document.querySelector('.adesione-terapie-module');
    if (!moduleRoot) {
      return;
    }

    const { initializeAdesioneTerapieModule } = await import('./index.js');
    initializeAdesioneTerapieModule({ routesBase, csrfToken, moduleRoot });
  });
})();
