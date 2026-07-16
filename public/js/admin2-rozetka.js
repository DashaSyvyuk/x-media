(function () {
    const toggleTokenMeta = document.querySelector('meta[name="rozetka-toggle-token"]');
    const toggleToken = toggleTokenMeta ? toggleTokenMeta.content : '';
    const valueFetchControllers = new WeakMap();

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

    function guardFormDoubleSubmit(form) {
        if (!form || form.dataset.doubleSubmitBound === '1') {
            return;
        }

        form.dataset.doubleSubmitBound = '1';
        form.addEventListener('submit', (event) => {
            if (form.dataset.submitting === '1') {
                event.preventDefault();
                return;
            }

            form.dataset.submitting = '1';

            const submitter = event.submitter;
            form.querySelectorAll('button[type="submit"], input[type="submit"]').forEach((button) => {
                if (submitter && button === submitter && submitter.name) {
                    const hidden = document.createElement('input');
                    hidden.type = 'hidden';
                    hidden.name = submitter.name;
                    hidden.value = submitter.value;
                    form.appendChild(hidden);
                }
                button.disabled = true;
            });
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

            if (label?.classList.contains('is-loading')) {
                input.checked = previous;
                return;
            }

            if (!id || !field || !toggleToken) {
                input.checked = previous;
                return;
            }

            label?.classList.add('is-loading');
            input.disabled = true;

            try {
                const body = new FormData();
                body.append('_token', toggleToken);
                body.append('field', field);
                body.append('value', value ? '1' : '0');

                const response = await fetch('/admin/rozetka/' + id + '/toggle', {
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
                const readyInput = row?.querySelector('.js-rozetka-toggle[data-field="ready"]');
                const readyOn = readyInput ? readyInput.checked : true;
                if (field === 'activeForA' || field === 'activeForP') {
                    input.disabled = !readyOn;
                } else {
                    input.disabled = false;
                }
            }
        });
    });

    function getCharacteristicSelects() {
        const collection = document.getElementById('rozetka-values-collection');
        if (!collection) {
            return [];
        }

        return Array.from(collection.querySelectorAll('select.characteristic'));
    }

    /** Hide parameters already chosen in other rows on this page. */
    function syncUsedCharacteristics() {
        const selects = getCharacteristicSelects();
        const used = new Set(
            selects.map((select) => select.value).filter(Boolean),
        );

        selects.forEach((select) => {
            const current = select.value;
            Array.from(select.options).forEach((option) => {
                if (!option.value) {
                    return;
                }

                const takenElsewhere = used.has(option.value) && option.value !== current;
                option.hidden = takenElsewhere;
                option.disabled = takenElsewhere;
            });
        });
    }

    document.addEventListener('change', (event) => {
        const target = event.target;
        if (!(target instanceof HTMLSelectElement) || !target.classList.contains('characteristic')) {
            return;
        }

        syncUsedCharacteristics();

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

        const previousController = valueFetchControllers.get(row);
        previousController?.abort();
        const controller = new AbortController();
        valueFetchControllers.set(row, controller);

        target.disabled = true;

        fetch(url, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            signal: controller.signal,
        })
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

                // Refresh collapsed summary without re-firing the characteristic change
                // handler (that would loop fetch requests forever).
                const collectionItem = row.closest('[data-collapsible]');
                if (collectionItem) {
                    collectionItem.dispatchEvent(new Event('change', { bubbles: true }));
                }
            })
            .catch((error) => {
                if (error?.name === 'AbortError') {
                    return;
                }
            })
            .finally(() => {
                if (valueFetchControllers.get(row) === controller) {
                    target.disabled = false;
                    syncUsedCharacteristics();
                }
            });
    });

    document.addEventListener('click', (event) => {
        const target = event.target;
        if (!(target instanceof HTMLElement)) {
            return;
        }

        const addButton = target.closest('[data-collection-add="#rozetka-values-collection"]');
        const removeButton = target.closest('[data-collection-remove]');
        const removedFromRozetka = removeButton
            && removeButton.closest('#rozetka-values-collection');

        if (!addButton && !removedFromRozetka) {
            return;
        }

        // Run after collection.js mutates the DOM.
        window.setTimeout(syncUsedCharacteristics, 0);
    });

    guardFormDoubleSubmit(document.getElementById('rozetka-edit-form'));
    syncUsedCharacteristics();
})();
