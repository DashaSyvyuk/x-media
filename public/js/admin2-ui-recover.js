(function () {
    const hasOrphanOverlay = () => {
        const bodyLocked = document.body.classList.contains('modal-open')
            || document.body.classList.contains('sidebar-open');
        const backdrop = document.querySelector('.modal-backdrop');
        const openModal = document.querySelector('.modal.show');

        return Boolean((bodyLocked || backdrop) && !openModal);
    };

    const recoverUi = () => {
        document.body.classList.remove('modal-open');
        document.body.style.removeProperty('overflow');
        document.body.style.removeProperty('padding-right');

        document.querySelectorAll('.modal-backdrop').forEach((el) => el.remove());

        document.querySelectorAll('.modal.show').forEach((modalEl) => {
            modalEl.classList.remove('show');
            modalEl.style.display = 'none';
            modalEl.setAttribute('aria-hidden', 'true');
            modalEl.removeAttribute('aria-modal');
            modalEl.removeAttribute('role');
        });

        // Sticky sidebar lock also blocks scrolling / taps after backgrounding.
        if (document.body.classList.contains('sidebar-open') && !document.querySelector('.sidebar.open')) {
            document.body.classList.remove('sidebar-open');
        }
    };

    window.addEventListener('pageshow', recoverUi);

    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'visible' && hasOrphanOverlay()) {
            recoverUi();
        }
    });
})();
