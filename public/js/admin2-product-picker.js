(function () {
    function debounce(fn, ms) {
        let timer;
        return (...args) => {
            clearTimeout(timer);
            timer = setTimeout(() => fn(...args), ms);
        };
    }

    function minQueryLength(query) {
        return /^\d+$/.test(query) ? 1 : 2;
    }

    function renderResults(picker, items) {
        const box = picker.querySelector('.admin2-product-picker__results');
        if (!box) {
            return;
        }

        if (!items.length) {
            box.innerHTML = '<div class="admin2-product-picker__empty">Нічого не знайдено</div>';
            box.classList.add('is-open');
            return;
        }

        box.innerHTML = items.map((item) => `
            <button type="button" class="admin2-product-picker__option" data-id="${item.id}" data-price="${item.price}">
                <span class="admin2-product-picker__option-id">#${item.id}</span>
                <span class="admin2-product-picker__option-title">${escapeHtml(item.title)}</span>
                ${item.productCode ? `<span class="admin2-product-picker__option-code">${escapeHtml(item.productCode)}</span>` : ''}
                <span class="admin2-product-picker__option-price">${formatPrice(item.price)}</span>
            </button>
        `).join('');
        box.classList.add('is-open');
    }

    function escapeHtml(text) {
        return String(text)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function formatPrice(value) {
        return `${Number(value || 0).toLocaleString('uk-UA')} ₴`;
    }

    function setSelected(picker, product, autoFillPrice = true) {
        const hidden = picker.querySelector('input[type="hidden"]');
        const selected = picker.querySelector('.admin2-product-picker__selected');
        const panel = picker.querySelector('.admin2-product-picker__panel');
        const idBadge = picker.querySelector('.admin2-product-picker__id-badge');
        const title = picker.querySelector('.admin2-product-picker__title');
        const results = picker.querySelector('.admin2-product-picker__results');
        const queryInput = picker.querySelector('.admin2-product-picker__query-input');

        if (!hidden || !selected || !panel) {
            return;
        }

        hidden.value = product.id;
        idBadge.textContent = `#${product.id}`;
        title.textContent = product.title;
        selected.classList.remove('d-none');
        panel.classList.add('d-none');
        results?.classList.remove('is-open');
        if (results) {
            results.innerHTML = '';
        }
        if (queryInput) {
            queryInput.value = '';
        }

        if (autoFillPrice) {
            const row = picker.closest('.order-item-entry');
            const priceInput = row?.querySelector('input[name*="[price]"]');
            if (priceInput && (!priceInput.value || priceInput.value === '0')) {
                priceInput.value = product.price;
            }
        }
    }

    function clearSelected(picker) {
        const hidden = picker.querySelector('input[type="hidden"]');
        const selected = picker.querySelector('.admin2-product-picker__selected');
        const panel = picker.querySelector('.admin2-product-picker__panel');

        if (hidden) {
            hidden.value = '';
        }
        selected?.classList.add('d-none');
        panel?.classList.remove('d-none');
    }

    async function searchProducts(picker, query) {
        const url = new URL(picker.dataset.searchUrl, window.location.origin);
        url.searchParams.set('q', query);
        const response = await fetch(url, { headers: { Accept: 'application/json' } });
        if (!response.ok) {
            return [];
        }
        return response.json();
    }

    function bindPicker(picker) {
        if (picker.dataset.bound === '1') {
            return;
        }
        picker.dataset.bound = '1';

        const queryInput = picker.querySelector('.admin2-product-picker__query-input');
        const results = picker.querySelector('.admin2-product-picker__results');
        const clearBtn = picker.querySelector('.admin2-product-picker__clear');

        const normalizeQuery = (value) => (value || '').trim().replace(/^#+/, '');

        const runSearch = debounce(async () => {
            const query = normalizeQuery(queryInput?.value);
            if (query.length < minQueryLength(query)) {
                results?.classList.remove('is-open');
                if (results) {
                    results.innerHTML = '';
                }
                return;
            }
            try {
                renderResults(picker, await searchProducts(picker, query));
            } catch {
                renderResults(picker, []);
            }
        }, 250);

        queryInput?.addEventListener('input', runSearch);

        queryInput?.addEventListener('keydown', async (event) => {
            if (event.key !== 'Enter') {
                return;
            }
            event.preventDefault();
            const query = normalizeQuery(queryInput.value);
            if (query.length < minQueryLength(query)) {
                return;
            }
            const items = await searchProducts(picker, query);
            if (items.length === 1 && /^\d+$/.test(query)) {
                setSelected(picker, items[0]);
                return;
            }
            renderResults(picker, items);
        });

        queryInput?.addEventListener('focus', () => {
            const query = normalizeQuery(queryInput.value);
            if (query.length >= minQueryLength(query) && results?.innerHTML) {
                results.classList.add('is-open');
            }
        });

        results?.addEventListener('click', (event) => {
            const option = event.target.closest('.admin2-product-picker__option');
            if (!option) {
                return;
            }
            setSelected(picker, {
                id: parseInt(option.dataset.id, 10),
                title: option.querySelector('.admin2-product-picker__option-title')?.textContent || '',
                price: parseInt(option.dataset.price, 10) || 0,
            });
        });

        clearBtn?.addEventListener('click', () => clearSelected(picker));

        document.addEventListener('click', (event) => {
            if (!picker.contains(event.target)) {
                results?.classList.remove('is-open');
            }
        });
    }

    function init(scope) {
        (scope || document).querySelectorAll('.admin2-product-picker').forEach(bindPicker);
    }

    window.Admin2ProductPicker = { init };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => init());
    } else {
        init();
    }
})();
