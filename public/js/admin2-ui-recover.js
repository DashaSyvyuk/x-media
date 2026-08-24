(function () {
    const hasOrphanOverlay = () => {
        const bodyLocked = document.body.classList.contains('modal-open')
            || document.body.classList.contains('sidebar-open');
        const backdrop = document.querySelector('.modal-backdrop, .sidebar-backdrop.show');
        const openModal = document.querySelector('.modal.show');
        const openSidebar = document.querySelector('.sidebar.open');

        return Boolean(
            ((bodyLocked || backdrop) && !openModal && !openSidebar)
            || (document.querySelector('.modal-backdrop') && !openModal),
        );
    };

    const recoverUi = () => {
        document.body.classList.remove('modal-open');
        document.body.style.removeProperty('overflow');
        document.body.style.removeProperty('overflow-y');
        document.body.style.removeProperty('padding-right');
        document.body.style.removeProperty('touch-action');
        document.documentElement.style.removeProperty('overflow');

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
            document.getElementById('sidebarBackdrop')?.classList.remove('show');
            document.getElementById('sidebarBackdrop')?.setAttribute('aria-hidden', 'true');
        }

        document.querySelectorAll('form[data-submitting="1"]').forEach((form) => {
            delete form.dataset.submitting;
            form.removeAttribute('aria-busy');
            form.querySelectorAll('button[type="submit"], input[type="submit"]').forEach((btn) => {
                btn.disabled = false;
                btn.classList.remove('is-submitting');
            });
        });

        // Re-enable selects that some WebViews leave inert after overlay/bfcache.
        document.querySelectorAll('select').forEach((select) => {
            if (select.style.pointerEvents === 'none') {
                select.style.removeProperty('pointer-events');
            }
            select.style.removeProperty('touch-action');
        });
    };

    window.addEventListener('pageshow', recoverUi);

    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'visible') {
            // Always recover on return — orphan check alone misses some freezes.
            if (hasOrphanOverlay() || document.querySelector('.modal-backdrop')) {
                recoverUi();
            }
        }
    });

    // Safety: if user taps a select and something still blocks it, clear overlays.
    document.addEventListener('pointerdown', (event) => {
        const select = event.target instanceof Element
            ? event.target.closest('select, .form-select')
            : null;
        if (!select) {
            return;
        }
        if (hasOrphanOverlay() || document.querySelector('.modal-backdrop:not(.show)')) {
            recoverUi();
        }
    }, true);
})();
