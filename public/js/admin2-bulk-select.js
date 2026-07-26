document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-bulk-root]').forEach((root) => {
        const selectAll = root.querySelector('[data-bulk-select-all]');
        const items = () => Array.from(root.querySelectorAll('[data-bulk-item]'));
        const bar = root.querySelector('[data-bulk-bar]');
        const countEl = root.querySelector('[data-bulk-count]');

        const forms = () => {
            const inside = Array.from(root.querySelectorAll('[data-bulk-form]'));
            if (!root.id) {
                return inside;
            }

            const linked = Array.from(
                document.querySelectorAll(`[data-bulk-form][data-bulk-for="${CSS.escape(root.id)}"]`),
            );

            return [...new Set([...inside, ...linked])];
        };

        const selectedIds = () => items()
            .filter((input) => input.checked)
            .map((input) => String(input.value))
            .filter((id) => id !== '');

        const sync = () => {
            const ids = selectedIds();
            const total = items().length;
            const selected = ids.length;

            if (selectAll) {
                selectAll.checked = total > 0 && selected === total;
                selectAll.indeterminate = selected > 0 && selected < total;
            }

            if (bar) {
                bar.classList.toggle('is-visible', selected > 0);
            }

            if (countEl) {
                countEl.textContent = String(selected);
            }

            forms().forEach((form) => {
                form.querySelectorAll('input[data-bulk-id]').forEach((input) => input.remove());
                ids.forEach((id) => {
                    const hidden = document.createElement('input');
                    hidden.type = 'hidden';
                    hidden.name = 'ids[]';
                    hidden.value = id;
                    hidden.setAttribute('data-bulk-id', '1');
                    form.appendChild(hidden);
                });
            });
        };

        selectAll?.addEventListener('change', () => {
            const checked = selectAll.checked;
            items().forEach((input) => {
                input.checked = checked;
            });
            sync();
        });

        root.addEventListener('change', (event) => {
            const target = event.target;
            if (!(target instanceof HTMLInputElement)) {
                return;
            }
            if (target.matches('[data-bulk-item], [data-bulk-select-all]')) {
                sync();
            }
        });

        forms().forEach((form) => {
            form.addEventListener('submit', (event) => {
                const ids = selectedIds();
                if (ids.length === 0) {
                    event.preventDefault();
                    return;
                }

                const template = form.dataset.confirm || '';
                if (template) {
                    const message = template.replace('{count}', String(ids.length));
                    if (!window.confirm(message)) {
                        event.preventDefault();
                    }
                }
            });
        });

        sync();
    });
});
