(function () {
    document.addEventListener('DOMContentLoaded', async () => {
        const routesBase = 'adesione-terapie/routes.php';
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        const moduleRoot = document.querySelector('.adesione-terapie-module');
        if (!moduleRoot) {
            return;
        }

        const { initializeAdesioneTerapieModule } = await import('./index.js');
        initializeAdesioneTerapieModule({ routesBase, csrfToken, moduleRoot });
    });
})();
