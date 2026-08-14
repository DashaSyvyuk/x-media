(function () {
    const table = document.getElementById('priceControlTable');
    if (!table) {
        return;
    }

    const updateUrl = table.dataset.updateUrl;
    const xkomUrlUpdateUrl = table.dataset.xkomUrlUpdateUrl;
    const token = table.dataset.updateToken;
    const xkomModalEl = document.getElementById('xkomUrlModal');
    const xkomProductIdEl = document.getElementById('xkomUrlProductId');
    const xkomUrlInputEl = document.getElementById('xkomUrlInput');
    const xkomSaveBtn = document.getElementById('xkomUrlSaveBtn');
    const xkomModal = xkomModalEl && window.bootstrap?.Modal ? new window.bootstrap.Modal(xkomModalEl) : null;

    // Idle autosave only as a backup; primary triggers are blur / Enter / status / button.
    const IDLE_SAVE_MS = 2500;
    const saveTimers = new WeakMap();
    const saveControllers = new WeakMap();
    const saveTokens = new WeakMap();

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

    function scheduleIdleSave(row) {
        cancelScheduledSave(row);
        const timer = window.setTimeout(() => {
            saveTimers.delete(row);
            // Only save if the user is no longer typing in this row.
            if (row.contains(document.activeElement)) {
                scheduleIdleSave(row);
                return;
            }
            void saveRow(row);
        }, IDLE_SAVE_MS);
        saveTimers.set(row, timer);
    }

    function applyServerValues(row, json) {
        const active = document.activeElement;
        const applyValue = (field, value) => {
            const input = fieldInput(row, field);
            if (!input) {
                return;
            }
            // Never clobber the field the user is still editing.
            if (active === input) {
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
        if (!row || !updateUrl || !token || !button) {
            return;
        }

        if (!row.classList.contains('is-dirty') && button.disabled) {
            return;
        }

        if (!validateRow(row)) {
            clearRowState(row);
            row.classList.add('is-error');
            setButtonState(button, 'err');
            button.disabled = false;
            return;
        }

        cancelScheduledSave(row);

        const previous = saveControllers.get(row);
        if (previous) {
            previous.abort();
        }

        const controller = new AbortController();
        saveControllers.set(row, controller);

        const tokenValue = (saveTokens.get(row) || 0) + 1;
        saveTokens.set(row, tokenValue);

        const payload = readPayload(row);
        const sentKey = payloadKey(payload);

        button.disabled = true;
        setButtonState(button, 'saving');
        row.classList.add('is-dirty');

        try {
            const response = await fetch(updateUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify(payload),
                signal: controller.signal,
            });

            const json = await response.json().catch(() => ({}));
            if (!response.ok || !json.ok) {
                throw new Error(json.error || 'save failed');
            }

            // A newer edit started another save — ignore this response.
            if (saveTokens.get(row) !== tokenValue) {
                return;
            }

            const currentKey = payloadKey(readPayload(row));
            if (currentKey !== sentKey) {
                // User kept typing while the request was in flight.
                markDirty(row);
                if (!row.contains(document.activeElement)) {
                    scheduleIdleSave(row);
                }
                return;
            }

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

    table.querySelectorAll('tbody tr').forEach((row) => validateRow(row));

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
        // Do not save on every pause mid-number — only arm a long idle backup.
        scheduleIdleSave(row);
    });

    table.addEventListener('focusout', (event) => {
        const input = event.target;
        if (!(input instanceof HTMLInputElement) || !input.closest('[data-field]')) {
            return;
        }

        const row = input.closest('tr');
        if (!row || !row.classList.contains('is-dirty')) {
            return;
        }

        // Leaving the row entirely → save now. Moving to another price cell in
        // the same row waits until blur of the row (relatedTarget check).
        const next = event.relatedTarget;
        if (next instanceof Node && row.contains(next)) {
            return;
        }

        cancelScheduledSave(row);
        void saveRow(row);
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

        input.blur();
        cancelScheduledSave(row);
        markDirty(row);
        void saveRow(row);
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
        cancelScheduledSave(row);
        void saveRow(row);
    });

    table.addEventListener('click', (event) => {
        const target = event.target;
        if (!(target instanceof HTMLElement)) {
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
            return;
        }

        const button = target.closest('.js-price-save');
        if (!button) {
            return;
        }

        const row = button.closest('tr');
        if (!row) {
            return;
        }

        cancelScheduledSave(row);
        markDirty(row);
        void saveRow(row);
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
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({ id, xkom_url: url, _token: token }),
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
