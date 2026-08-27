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
        if (typeof window.__admin2AbortPullRefresh === 'function') {
            window.__admin2AbortPullRefresh();
        }

        document.body.classList.remove('modal-open');
        document.body.style.removeProperty('overflow');
        document.body.style.removeProperty('overflow-y');
        document.body.style.removeProperty('overflow-x');
        document.body.style.removeProperty('padding-right');
        document.body.style.removeProperty('touch-action');
        document.documentElement.style.removeProperty('overflow');
        document.documentElement.style.removeProperty('overflow-x');
        document.documentElement.style.removeProperty('touch-action');

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

        const sidebarBackdrop = document.getElementById('sidebarBackdrop');
        if (sidebarBackdrop && !document.querySelector('.sidebar.open')) {
            sidebarBackdrop.classList.remove('show');
            sidebarBackdrop.setAttribute('aria-hidden', 'true');
        }

        document.querySelectorAll('form[data-submitting="1"]').forEach((form) => {
            delete form.dataset.submitting;
            form.removeAttribute('aria-busy');
            form.querySelectorAll('button[type="submit"], input[type="submit"]').forEach((btn) => {
                btn.disabled = false;
                btn.classList.remove('is-submitting');
            });
        });

        // Re-enable selects that some WebViews leave inert after overlay/bfcache/AJAX.
        document.querySelectorAll('select').forEach((select) => {
            if (select.style.pointerEvents === 'none') {
                select.style.removeProperty('pointer-events');
            }
            select.style.removeProperty('touch-action');
            // Transient AJAX lock without an in-flight marker — unlock.
            if (select.disabled && select.dataset.ajaxLock === '1' && !select.dataset.ajaxPending) {
                select.disabled = false;
                delete select.dataset.ajaxLock;
            }
        });
    };

    window.__admin2RecoverUi = recoverUi;

    window.addEventListener('pageshow', recoverUi);

    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'visible') {
            // Always recover on return — orphan check alone misses freezes.
            recoverUi();
        }
    });

    // Safety: if user taps a select and something still blocks it, clear overlays.
    const onSelectIntent = (event) => {
        const select = event.target instanceof Element
            ? event.target.closest('select, .form-select')
            : null;
        if (!select) {
            return;
        }
        if (hasOrphanOverlay() || document.querySelector('.modal-backdrop')) {
            recoverUi();
        } else if (typeof window.__admin2AbortPullRefresh === 'function') {
            window.__admin2AbortPullRefresh();
        }
    };

    document.addEventListener('pointerdown', onSelectIntent, true);
    document.addEventListener('touchstart', onSelectIntent, true);
})();
