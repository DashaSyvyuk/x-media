(function () {
    /**
     * Returns true when there is a leftover modal backdrop or body lock that
     * has no matching open modal — happens after bfcache restore or app
     * backgrounding on iOS/Android.
     */
    const hasOrphanOverlay = () => {
        const bodyLocked = document.body.classList.contains('modal-open')
            || document.body.classList.contains('sidebar-open');
        const backdrop   = document.querySelector('.modal-backdrop');
        const openModal  = document.querySelector('.modal.show');
        return Boolean((bodyLocked || backdrop) && !openModal);
    };

    const recoverUi = () => {
        // ---- body class / style locks ----
        document.body.classList.remove('modal-open', 'sidebar-open');
        document.body.style.removeProperty('overflow');
        document.body.style.removeProperty('overflow-y');
        document.body.style.removeProperty('padding-right');
        document.body.style.removeProperty('touch-action');

        // ---- orphan backdrops ----
        document.querySelectorAll('.modal-backdrop').forEach((el) => el.remove());

        // ---- stale open modals ----
        document.querySelectorAll('.modal.show').forEach((modalEl) => {
            modalEl.classList.remove('show');
            modalEl.style.display = 'none';
            modalEl.setAttribute('aria-hidden', 'true');
            modalEl.removeAttribute('aria-modal');
            modalEl.removeAttribute('role');
        });

        // ---- unlock locked forms (bfcache leaves them disabled) ----
        document.querySelectorAll('form[data-submitting="1"]').forEach((form) => {
            delete form.dataset.submitting;
            form.removeAttribute('aria-busy');
            form.querySelectorAll('button[type="submit"], input[type="submit"]').forEach((btn) => {
                btn.disabled = false;
                btn.classList.remove('is-submitting');
            });
        });

        // ---- pointer-events that may linger on any element ----
        document.querySelectorAll('[style*="pointer-events"]').forEach((el) => {
            // Only clear if it was set to none and the element is not intentionally hidden.
            if (window.getComputedStyle(el).pointerEvents === 'none' && !el.disabled) {
                el.style.removeProperty('pointer-events');
            }
        });
    };

    // Run on every bfcache restore (persisted=true) and on normal page load.
    window.addEventListener('pageshow', recoverUi);

    // Run when the tab becomes visible again (app switch on mobile).
    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'visible' && hasOrphanOverlay()) {
            recoverUi();
        }
    });
})();
