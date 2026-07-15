(function () {
    if (!('serviceWorker' in navigator)) {
        return;
    }

    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/admin2/sw.js', { scope: '/admin2/' }).catch(() => {
            /* SW optional — admin still works without it */
        });
    });
})();
