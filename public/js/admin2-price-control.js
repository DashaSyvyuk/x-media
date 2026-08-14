(function () {
    const table = document.getElementById('priceControlTable');
    if (!table) {
        return;
    }

    const updateUrl = table.dataset.updateUrl || '';
    const xkomUrlUpdateUrl = table.dataset.xkomUrlUpdateUrl || '';
    const token = table.dataset.updateToken || '';
    const xkomModalEl = document.getElementById('xkomUrlModal');
    const xkomProductIdEl = document.getElementById('xkomUrlProductId');
    const xkomUrlInputEl = document.getElementById('xkomUrlInput');
    const xkomSaveBtn = document.getElementById('xkomUrlSaveBtn');
    const xkomModal = xkomModalEl && window.bootstrap?.Modal ? new window.bootstrap.Modal(xkomModalEl) : null;

    // Pause while typing, then save. Blur / Enter / status / button save immediately.
    const DEBOUNCE_MS = 900;
    const saveTimers = new WeakMap();
    const saveControllers = new WeakMap();
    const saveRequestIds = new WeakMap();
    const lastSavedKeys = new WeakMap();

    function numVal(value) {
        if (value === null || value === undefined) {
            return null;
        }
        const normalized = String(value).trim().replace(',', '.');
        if (normalized === '') {
            return null;
        }
        const parsed = parseFloat(normalized);
        return Number.isNaN(parsed) ? null : parsed;
    }

    function setButtonState(button, state) {
        button.classList.remove('btn-teal', 'btn-success', 'btn-danger');
        if (state === 'ok') {
            button.classList.add('btn-success');
            button.textContent = 'Збережено';
        } else if (state === 'err') {
            button.classList.add('btn-danger');
            button.textContent = 'Помилка';
        } else if (state === 'saving') {
            button.classList.add('btn-teal');
            button.textContent = 'Збереження…';
        } else {
            button.classList.add('btn-teal');
            button.textContent = 'Зберегти';
        }
    }

    function clearRowState(row) {
        row.classList.remove('is-dirty', 'is-saved', 'is-error');
    }

    function markDirty(row) {
        clearRowState(row);
        row.classList.add('is-dirty');
        const button = row.querySelector('.js-price-save');
        if (button) {
            button.disabled = false;
            setButtonState(button, 'idle');
        }
    }

    function fieldInput(row, field) {
        return row.querySelector(`[data-field="${field}"] input`);
    }

    function readPayload(row) {
        return {
            id: Number(row.dataset.id),
            price: fieldInput(row, 'price')?.value ?? '',
            crossed_out_price: fieldInput(row, 'crossed_out_price')?.value ?? '',
            rozetka_price: fieldInput(row, 'rozetka_price')?.value ?? '',
            rozetka_crossed_out_price: fieldInput(row, 'rozetka_crossed_out_price')?.value ?? '',
            status: row.dataset.status || 'Активний',
            _token: token,
        };
    }

    function payloadKey(payload) {
        return [
            payload.price,
            payload.crossed_out_price,
            payload.rozetka_price,
            payload.rozetka_crossed_out_price,
            payload.status,
        ].join('\u0001');
    }

    function validateRow(row) {
        const oldCell = row.querySelector('[data-field="crossed_out_price"]');
        const rzPriceCell = row.querySelector('[data-field="rozetka_price"]');
        const rzOldCell = row.querySelector('[data-field="rozetka_crossed_out_price"]');

        [oldCell, rzPriceCell, rzOldCell].forEach((cell) => cell?.classList.remove('cell-bad'));

        const price = numVal(fieldInput(row, 'price')?.value);
        const oldPrice = numVal(fieldInput(row, 'crossed_out_price')?.value);
        const rzPrice = numVal(fieldInput(row, 'rozetka_price')?.value);
        const rzOld = numVal(fieldInput(row, 'rozetka_crossed_out_price')?.value);

        if (price !== null && oldPrice !== null && oldPrice <= price) {
            oldCell?.classList.add('cell-bad');
        }
        if (rzPrice !== null && rzOld !== null && rzOld <= rzPrice) {
            rzOldCell?.classList.add('cell-bad');
        }
        if (price !== null && rzPrice !== null && rzPrice > price && rzPrice <= price * 1.05) {
            rzPriceCell?.classList.add('cell-bad');
        }

        return price !== null;
    }

    function cancelScheduledSave(row) {
        const existing = saveTimers.get(row);
        if (existing) {
            window.clearTimeout(existing);
            saveTimers.delete(row);
        }
    }

    function scheduleSave(row) {
        cancelScheduledSave(row);
        const timer = window.setTimeout(() => {
            saveTimers.delete(row);
            void saveRow(row);
        }, DEBOUNCE_MS);
        saveTimers.set(row, timer);
    }

    function saveNow(row) {
        cancelScheduledSave(row);
        void saveRow(row);
    }

    function applyServerValues(row, json) {
        const active = document.activeElement;
        const applyValue = (field, value) => {
            const input = fieldInput(row, field);
            if (!input || active === input) {
                return;
            }
            input.value = value === null || value === undefined ? '' : String(value);
        };

        applyValue('price', json.price);
        applyValue('crossed_out_price', json.crossed_out_price);
        applyValue('rozetka_price', json.rozetka_price);
        applyValue('rozetka_crossed_out_price', json.rozetka_crossed_out_price);
        validateRow(row);
    }

    async function saveRow(row) {
        const button = row.querySelector('.js-price-save');
        if (!row || !button) {
            return;
        }

        if (!updateUrl || !token) {
            clearRowState(row);
            row.classList.add('is-error');
            setButtonState(button, 'err');
            button.disabled = false;
            return;
        }

        if (!validateRow(row)) {
            clearRowState(row);
            row.classList.add('is-error');
            setButtonState(button, 'err');
            button.disabled = false;
            return;
        }

        const payload = readPayload(row);
        const sentKey = payloadKey(payload);

        if (lastSavedKeys.get(row) === sentKey && !row.classList.contains('is-dirty')) {
            return;
        }

        const previous = saveControllers.get(row);
        if (previous) {
            previous.abort();
        }

        const controller = new AbortController();
        saveControllers.set(row, controller);

        const requestId = (saveRequestIds.get(row) || 0) + 1;
        saveRequestIds.set(row, requestId);

        button.disabled = true;
        setButtonState(button, 'saving');
        row.classList.remove('is-saved', 'is-error');
        row.classList.add('is-dirty');

        try {
            const response = await fetch(updateUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify(payload),
                signal: controller.signal,
                credentials: 'same-origin',
            });

            const json = await response.json().catch(() => ({}));
            if (!response.ok || !json.ok) {
                throw new Error(json.error || ('HTTP ' + response.status));
            }

            // Superseded by a newer save attempt.
            if (saveRequestIds.get(row) !== requestId) {
                return;
            }

            const currentKey = payloadKey(readPayload(row));
            if (currentKey !== sentKey) {
                // User typed more while request was in flight — save again.
                markDirty(row);
                scheduleSave(row);
                return;
            }

            lastSavedKeys.set(row, sentKey);
            applyServerValues(row, json);
            clearRowState(row);
            row.classList.add('is-saved');
            setButtonState(button, 'ok');
            button.disabled = true;
            window.setTimeout(() => {
                if (row.classList.contains('is-saved')) {
                    row.classList.remove('is-saved');
                }
            }, 900);
        } catch (error) {
            if (error?.name === 'AbortError') {
                return;
            }

            if (saveRequestIds.get(row) !== requestId) {
                return;
            }

            clearRowState(row);
            row.classList.add('is-error');
            setButtonState(button, 'err');
            button.disabled = false;
        } finally {
            if (saveControllers.get(row) === controller) {
                saveControllers.delete(row);
            }
        }
    }

    table.querySelectorAll('tbody tr').forEach((row) => {
        validateRow(row);
        lastSavedKeys.set(row, payloadKey(readPayload(row)));
    });

    table.addEventListener('input', (event) => {
        const input = event.target;
        if (!(input instanceof HTMLInputElement) || !input.closest('[data-field]')) {
            return;
        }

        const row = input.closest('tr');
        if (!row) {
            return;
        }

        validateRow(row);
        markDirty(row);
        scheduleSave(row);
    });

    // After leaving a price field: save soon. Delay lets focus move to another
    // cell / Save button without double-firing awkwardly.
    table.addEventListener('focusout', (event) => {
        const input = event.target;
        if (!(input instanceof HTMLInputElement) || !input.closest('[data-field]')) {
            return;
        }

        const row = input.closest('tr');
        if (!row || !row.classList.contains('is-dirty')) {
            return;
        }

        window.setTimeout(() => {
            if (!row.classList.contains('is-dirty')) {
                return;
            }

            const active = document.activeElement;
            if (
                active instanceof HTMLInputElement
                && active.closest('[data-field]')
                && row.contains(active)
            ) {
                // Still editing another price cell in this row — debounce handles it.
                return;
            }

            saveNow(row);
        }, 50);
    });

    table.addEventListener('keydown', (event) => {
        const input = event.target;
        if (!(input instanceof HTMLInputElement) || !input.closest('[data-field]')) {
            return;
        }
        if (event.key !== 'Enter') {
            return;
        }

        event.preventDefault();
        const row = input.closest('tr');
        if (!row) {
            return;
        }

        markDirty(row);
        saveNow(row);
        input.blur();
    });

    table.addEventListener('change', (event) => {
        const input = event.target;
        if (!(input instanceof HTMLInputElement) || !input.classList.contains('js-status-switch')) {
            return;
        }

        const row = input.closest('tr');
        if (!row) {
            return;
        }

        row.dataset.status = input.checked ? 'Активний' : 'Заблокований';
        markDirty(row);
        saveNow(row);
    });

    // pointerdown fires before input blur/focus changes — more reliable than click.
    table.addEventListener('pointerdown', (event) => {
        const target = event.target;
        if (!(target instanceof Element)) {
            return;
        }

        const button = target.closest('.js-price-save');
        if (!button || button.disabled) {
            return;
        }

        const row = button.closest('tr');
        if (!row) {
            return;
        }

        event.preventDefault();
        markDirty(row);
        saveNow(row);
    });

    table.addEventListener('click', (event) => {
        const target = event.target;
        if (!(target instanceof Element)) {
            return;
        }

        const xkomButton = target.closest('.js-xkom-url-edit');
        if (xkomButton) {
            const productId = xkomButton.getAttribute('data-product-id');
            if (xkomProductIdEl && xkomUrlInputEl && productId) {
                xkomProductIdEl.value = productId;
                xkomUrlInputEl.value = '';
                xkomModal?.show();
                xkomUrlInputEl.focus();
            }
        }
    });

    xkomSaveBtn?.addEventListener('click', async () => {
        if (!xkomUrlUpdateUrl || !token || !xkomProductIdEl || !xkomUrlInputEl) {
            return;
        }

        const id = Number(xkomProductIdEl.value);
        const url = String(xkomUrlInputEl.value || '').trim();
        if (!id || !url) {
            return;
        }

        xkomSaveBtn.disabled = true;
        try {
            const response = await fetch(xkomUrlUpdateUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({ id, xkom_url: url, _token: token }),
                credentials: 'same-origin',
            });

            const json = await response.json().catch(() => ({}));
            if (!response.ok || !json.ok) {
                throw new Error(json.error || 'save failed');
            }

            xkomModal?.hide();
            window.location.reload();
        } catch {
            window.alert('Не вдалося зберегти посилання x-kom');
            xkomSaveBtn.disabled = false;
        }
    });
})();
