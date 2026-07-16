(function () {
    if (!('serviceWorker' in navigator)) {
        return;
    }

    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/admin/sw.js', { scope: '/admin/' }).catch(() => {
            /* SW optional — admin still works without it */
        });
    });
})();
