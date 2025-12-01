(function () {

    document.addEventListener('DOMContentLoaded', async () => {
        const routesBase = 'adesione-terapie/routes.php';
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        const moduleRoot = document.querySelector('#therapyModal') || document.querySelector('.adesione-terapie-module');
        if (!moduleRoot) {
            return;

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
