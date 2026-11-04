/**
 * Make GET list-search toolbars reliable on mobile / PWA:
 * - Enter in the search field submits the form
 * - Changing category/status/warehouse/perPage auto-submits
 */
(function () {
    function closestToolbarForm(el) {
        return el instanceof Element ? el.closest('form.products-toolbar') : null;
    }

    document.addEventListener('keydown', (event) => {
        if (event.key !== 'Enter') {
            return;
        }

        const target = event.target;
        if (!(target instanceof HTMLInputElement)) {
            return;
        }

        if (target.type !== 'search' && target.name !== 'q') {
            return;
        }

        const form = closestToolbarForm(target);
        if (!form) {
            return;
        }

        event.preventDefault();
        if (typeof form.requestSubmit === 'function') {
            form.requestSubmit();
        } else {
            form.submit();
        }
    });

    document.addEventListener('change', (event) => {
        const target = event.target;
        if (!(target instanceof HTMLSelectElement)) {
            return;
        }

        const form = closestToolbarForm(target);
        if (!form) {
            return;
        }

        // Only auto-submit filter selects, not accidental custom selects outside toolbar.
        if (!['category', 'status', 'warehouse', 'perPage'].includes(target.name)) {
            return;
        }

        if (typeof form.requestSubmit === 'function') {
            form.requestSubmit();
        } else {
            form.submit();
        }
    });
})();
