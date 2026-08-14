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
        } else if (state === 'err') {
            button.classList.add('btn-danger');
        } else {
            button.classList.add('btn-teal');
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

    function validateRow(row) {
        const oldCell = row.querySelector('[data-field="crossed_out_price"]');
        const rzPriceCell = row.querySelector('[data-field="rozetka_price"]');
        const rzOldCell = row.querySelector('[data-field="rozetka_crossed_out_price"]');

        [oldCell, rzPriceCell, rzOldCell].forEach((cell) => cell?.classList.remove('cell-bad'));

        const price = numVal(row.querySelector('[data-field="price"] input')?.value);
        const oldPrice = numVal(row.querySelector('[data-field="crossed_out_price"] input')?.value);
        const rzPrice = numVal(row.querySelector('[data-field="rozetka_price"] input')?.value);
        const rzOld = numVal(row.querySelector('[data-field="rozetka_crossed_out_price"] input')?.value);

        if (price !== null && oldPrice !== null && oldPrice <= price) {
            oldCell?.classList.add('cell-bad');
        }
        if (rzPrice !== null && rzOld !== null && rzOld <= rzPrice) {
            rzOldCell?.classList.add('cell-bad');
        }
        if (price !== null && rzPrice !== null && rzPrice > price && rzPrice <= price * 1.05) {
            rzPriceCell?.classList.add('cell-bad');
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
    });

    table.addEventListener('click', async (event) => {
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
        if (!row || !updateUrl || !token) {
            return;
        }

        const payload = {
            id: Number(row.dataset.id),
            price: row.querySelector('[data-field="price"] input')?.value ?? '',
            crossed_out_price: row.querySelector('[data-field="crossed_out_price"] input')?.value ?? '',
            rozetka_price: row.querySelector('[data-field="rozetka_price"] input')?.value ?? '',
            rozetka_crossed_out_price: row.querySelector('[data-field="rozetka_crossed_out_price"] input')?.value ?? '',
            status: row.dataset.status || 'Активний',
            _token: token,
        };

        button.disabled = true;

        try {
            const response = await fetch(updateUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify(payload),
            });

            const json = await response.json().catch(() => ({}));
            if (!response.ok || !json.ok) {
                throw new Error(json.error || 'save failed');
            }

            const applyValue = (field, value) => {
                const input = row.querySelector(`[data-field="${field}"] input`);
                if (!input) {
                    return;
                }
                input.value = value === null || value === undefined ? '' : String(value);
            };

            applyValue('price', json.price);
            applyValue('crossed_out_price', json.crossed_out_price);
            applyValue('rozetka_price', json.rozetka_price);
            applyValue('rozetka_crossed_out_price', json.rozetka_crossed_out_price);
            validateRow(row);

            clearRowState(row);
            row.classList.add('is-saved');
            setButtonState(button, 'ok');
            button.disabled = true;
            window.setTimeout(() => row.classList.remove('is-saved'), 900);
        } catch (error) {
            clearRowState(row);
            row.classList.add('is-error');
            setButtonState(button, 'err');
            button.disabled = false;
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
