(function () {
    function decodeCopyText(button) {
        const raw = button.getAttribute('data-copy-text');
        if (!raw) {
            return '';
        }

        try {
            const text = JSON.parse(raw);

            return typeof text === 'string' ? text : '';
        } catch {
            return raw;
        }
    }

    function copyText(text) {
        if (navigator.clipboard?.writeText) {
            return navigator.clipboard.writeText(text);
        }

        const textarea = document.createElement('textarea');
        textarea.value = text;
        textarea.setAttribute('readonly', '');
        textarea.style.position = 'fixed';
        textarea.style.left = '-9999px';
        document.body.appendChild(textarea);
        textarea.select();

        try {
            document.execCommand('copy');
            return Promise.resolve();
        } finally {
            textarea.remove();
        }
    }

    document.addEventListener('click', (event) => {
        const button = event.target.closest('.order-copy-btn');
        if (!button) {
            return;
        }

        event.preventDefault();

        const text = decodeCopyText(button);
        if (!text) {
            return;
        }

        copyText(text)
            .then(() => {
                const icon = button.querySelector('i');
                if (!icon) {
                    return;
                }

                const originalClass = icon.className;
                icon.className = 'bi bi-check2';
                button.classList.add('text-success');
                button.setAttribute('title', 'Скопійовано');

                window.setTimeout(() => {
                    icon.className = originalClass;
                    button.classList.remove('text-success');
                    button.setAttribute('title', button.dataset.copyTitle || 'Скопіювати для відправки');
                }, 1500);
            })
            .catch(() => {
                window.alert('Не вдалося скопіювати текст');
            });
    });
})();
