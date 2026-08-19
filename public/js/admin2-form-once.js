(function () {
    const shouldGuard = (form) => {
        if (!(form instanceof HTMLFormElement)) {
            return false;
        }

        if (form.hasAttribute('data-allow-resubmit')) {
            return false;
        }

        const method = (form.getAttribute('method') || 'get').toLowerCase();
        return method === 'post';
    };

    const unlockForm = (form) => {
        delete form.dataset.submitting;
        form.removeAttribute('aria-busy');

        form.querySelectorAll('button[type="submit"], input[type="submit"]').forEach((control) => {
            control.disabled = false;
            control.classList.remove('is-submitting');
        });
    };

    const unlockAll = () => {
        document.querySelectorAll('form[data-submitting="1"]').forEach((form) => {
            unlockForm(form);
        });
    };

    const lockForm = (form, submitter) => {
        if (
            submitter
            && submitter.name
            && (submitter instanceof HTMLButtonElement || submitter instanceof HTMLInputElement)
        ) {
            const hidden = document.createElement('input');
            hidden.type = 'hidden';
            hidden.name = submitter.name;
            hidden.value = submitter.value;
            form.appendChild(hidden);
        }

        form.dataset.submitting = '1';
        form.setAttribute('aria-busy', 'true');

        form.querySelectorAll('button[type="submit"], input[type="submit"]').forEach((control) => {
            control.disabled = true;
            control.classList.add('is-submitting');
        });
    };

    document.addEventListener('submit', (event) => {
        const form = event.target;
        if (!shouldGuard(form) || event.defaultPrevented) {
            return;
        }

        if (form.dataset.submitting === '1') {
            event.preventDefault();
            return;
        }

        lockForm(form, event.submitter);
    });

    // PWA / Safari often restores pages from bfcache with locked forms.
    window.addEventListener('pageshow', unlockAll);
})();
