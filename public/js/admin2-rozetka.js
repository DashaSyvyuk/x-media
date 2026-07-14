(function () {
    const toggleTokenMeta = document.querySelector('meta[name="rozetka-toggle-token"]');
    const toggleToken = toggleTokenMeta ? toggleTokenMeta.content : '';

    function getCollectionIndex(container) {
        if (!container) {
            return null;
        }

        const id = container.id || container.getAttribute('name') || '';
        const match = id.match(/values_(\d+)_/);
        if (match) {
            return match[1];
        }

        const name = container.getAttribute('name') || '';
        const nameMatch = name.match(/\[values\]\[(\d+)\]/);
        return nameMatch ? nameMatch[1] : null;
    }

    function findValueContainer(row) {
        return row.querySelector('.rozetka-characteristics-values');
    }

    function getFormPrefix(form) {
        const holder = form.querySelector('[data-form-prefix]');
        if (holder && holder.dataset.formPrefix) {
            return holder.dataset.formPrefix;
        }

        return 'rozetka_product';
    }

    function updateFeedSwitchStates(row, ready) {
        ['activeForA', 'activeForP'].forEach((field) => {
            const input = row.querySelector('.js-rozetka-toggle[data-field="' + field + '"]');
            if (!input) {
                return;
            }

            const label = input.closest('.rozetka-inline-switch');
            input.disabled = !ready;
            if (label) {
                label.classList.toggle('is-disabled', !ready);
            }

            if (!ready) {
                input.checked = false;
            }
        });
    }

    document.querySelectorAll('.js-rozetka-toggle').forEach((input) => {
        input.addEventListener('change', async () => {
            const id = input.dataset.id;
            const field = input.dataset.field;
            const value = input.checked;
            const label = input.closest('.rozetka-inline-switch');
            const row = input.closest('tr');
            const previous = !value;

            if (!id || !field || !toggleToken) {
                input.checked = previous;
                return;
            }

            label?.classList.add('is-loading');

            try {
                const body = new FormData();
                body.append('_token', toggleToken);
                body.append('field', field);
                body.append('value', value ? '1' : '0');

                const response = await fetch('/admin2/rozetka/' + id + '/toggle', {
                    method: 'POST',
                    body,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                });

                const payload = await response.json().catch(() => ({}));

                if (!response.ok || !payload.ok) {
                    input.checked = previous;
                    window.alert(payload.error || 'Не вдалося оновити статус.');
                    return;
                }

                if (field === 'ready' && row) {
                    updateFeedSwitchStates(row, value);
                }
            } catch (error) {
                input.checked = previous;
                window.alert('Помилка мережі. Спрóбуйте ще раз.');
            } finally {
                label?.classList.remove('is-loading');
            }
        });
    });

    document.addEventListener('change', (event) => {
        const target = event.target;
        if (!(target instanceof HTMLSelectElement) || !target.classList.contains('characteristic')) {
            return;
        }

        const row = target.closest('.collection-item__body, .rozetka-value-row');
        const form = target.closest('form');
        if (!row || !form) {
            return;
        }

        const characteristicId = target.value;
        const valueContainer = findValueContainer(row);
        const valueIndex = getCollectionIndex(valueContainer) ?? getCollectionIndex(target);
        const prefix = getFormPrefix(form);

        if (!characteristicId || valueIndex === null) {
            return;
        }

        const url = '/api/v1/characteristics/' + encodeURIComponent(characteristicId)
            + '/values/' + encodeURIComponent(valueIndex)
            + '?prefix=' + encodeURIComponent(prefix);

        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then((response) => {
                if (!response.ok) {
                    throw new Error('Failed to load values');
                }
                return response.text();
            })
            .then((html) => {
                const slot = row.querySelector('[data-rozetka-value-slot]');
                if (slot) {
                    slot.innerHTML = html;
                } else if (valueContainer) {
                    valueContainer.outerHTML = html;
                }

                const collectionItem = row.closest('[data-collapsible]');
                const summaryTrigger = collectionItem?.querySelector('.characteristic');
                if (summaryTrigger) {
                    summaryTrigger.dispatchEvent(new Event('change', { bubbles: true }));
                }
            })
            .catch(() => {});
    });
})();
