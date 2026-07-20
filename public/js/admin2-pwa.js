(function () {
    if (!('serviceWorker' in navigator)) {
        return;
    }

    window.addEventListener('load', () => {
        navigator.serviceWorker
            .register('/admin/sw.js', { scope: '/admin/' })
            .then((registration) => {
                registration.update().catch(() => {});
                if (registration.waiting) {
                    registration.waiting.postMessage({ type: 'SKIP_WAITING' });
                }
            })
            .catch(() => {
                /* SW optional — admin still works without it */
            });
    });
})();
