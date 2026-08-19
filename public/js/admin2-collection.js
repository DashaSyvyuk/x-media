function getSelectLabel(select) {
    if (!(select instanceof HTMLSelectElement) || !select.value) {
        return '—';
    }

    const option = select.selectedOptions[0];
    if (!option || !option.value) {
        return '—';
    }

    return option.textContent.trim();
}

const collapsibleSummaryHandlers = {
    'filter-attribute': (item) => {
        return item.querySelector('[name*="[value]"]')?.value.trim() || '—';
    },
    'feed-price': (item) => {
        const feed = getSelectLabel(item.querySelector('select[name*="[feed]"]'));
        const ourPercent = item.querySelector('[name*="[ourPercent]"]')?.value.trim() || '—';
        const fee = item.querySelector('[name*="[fee]"]')?.value.trim() || '—';

        return feed + ' · ' + ourPercent + '% · ' + fee;
    },
    characteristic: (item) => {
        const title = item.querySelector('[name*="[title]"]')?.value.trim() || '—';
        const value = item.querySelector('[name*="[value]"]')?.value.trim() || '—';

        return title + ': ' + value;
    },
    filter: (item) => {
        const filterLabel = getSelectLabel(item.querySelector('.filter-select'));
        const attributeLabel = getSelectLabel(item.querySelector('.filter-attribute-select'));

        return filterLabel + ': ' + attributeLabel;
    },
    image: (item) => {
        const position = item.querySelector('[name*="[position]"]')?.value.trim() || '—';
        const fileInput = item.querySelector('input[type="file"]');
        let label = 'Зображення';

        if (fileInput?.files?.[0]?.name) {
            label = fileInput.files[0].name;
        } else {
            const preview = item.querySelector('.product-image-preview');
            if (preview?.src) {
                const parts = preview.src.split('/');
                label = decodeURIComponent(parts[parts.length - 1] || 'Зображення');
            }
        }

        return label + ' · пріоритет: ' + position;
    },
    'promotion-product': (item) => {
        const id = item.querySelector('[name*="[product]"]')?.value.trim();
        const label = item.querySelector('[data-product-label]')?.textContent.trim();
        if (label) {
            return label;
        }
        return id ? 'ID ' + id : '—';
    },
    'rozetka-characteristic': (item) => {
        const param = getSelectLabel(item.querySelector('.characteristic'));
        const valueField = item.querySelector('.rozetka-characteristics-values');

        if (valueField instanceof HTMLSelectElement) {
            if (valueField.multiple) {
                const selected = Array.from(valueField.selectedOptions).map((option) => option.textContent.trim()).filter(Boolean);
                return param + ': ' + (selected.length ? selected.join(', ') : '—');
            }
            return param + ': ' + getSelectLabel(valueField);
        }

        const textValue = valueField instanceof HTMLInputElement || valueField instanceof HTMLTextAreaElement
            ? valueField.value.trim()
            : '';

        return param + ': ' + (textValue || '—');
    },
};

function updateCollapsibleSummary(item) {
    const summaryEl = item.querySelector('[data-collapsible-summary]');
    if (!summaryEl) {
        return;
    }

    const handler = collapsibleSummaryHandlers[item.dataset.collapsibleType];
    summaryEl.textContent = handler ? handler(item) : '—';
}

function initCollapsibleItem(item) {
    updateCollapsibleSummary(item);
}

function wrapCollapsibleItem(bodyHtml, type, collapsed) {
    const collapsedClass = collapsed ? ' is-collapsed' : '';
    const ariaExpanded = collapsed ? 'false' : 'true';

    return '<div class="collection-item collection-item--collapsible' + collapsedClass + '" data-collapsible data-collapsible-type="' + type + '">' +
        '<div class="collection-item__toolbar">' +
        '<button type="button" class="collection-item__toggle" data-collapsible-toggle aria-expanded="' + ariaExpanded + '">' +
        '<i class="bi bi-chevron-down collection-item__chevron" aria-hidden="true"></i>' +
        '<span class="collection-item__summary text-truncate" data-collapsible-summary">—</span>' +
        '</button>' +
        '<button type="button" class="btn btn-sm btn-outline-danger collection-item__remove" data-collection-remove aria-label="Видалити">' +
        '<i class="bi bi-trash"></i></button>' +
        '</div>' +
        '<div class="collection-item__body" data-collapsible-body">' + bodyHtml + '</div>' +
        '</div>';
}

document.querySelectorAll('[data-collapsible]').forEach((item) => {
    initCollapsibleItem(item);
});

document.addEventListener('input', (event) => {
    const target = event.target;
    if (!(target instanceof HTMLElement)) {
        return;
    }

    const item = target.closest('[data-collapsible]');
    if (item) {
        updateCollapsibleSummary(item);
    }
});

document.addEventListener('change', (event) => {
    const target = event.target;
    if (!(target instanceof HTMLElement)) {
        return;
    }

    const item = target.closest('[data-collapsible]');
    if (item) {
        updateCollapsibleSummary(item);
    }

    if (!(target instanceof HTMLSelectElement) || !target.classList.contains('filter-select')) {
        return;
    }

    const filterId = target.value;
    const row = target.closest('.collection-item');
    const attributeSelect = row?.querySelector('.filter-attribute-select');

    if (!attributeSelect || !filterId) {
        return;
    }

    fetch('/api/v1/filter/' + filterId + '/filter_attributes')
        .then((response) => response.json())
        .then((items) => {
            attributeSelect.innerHTML = '<option value="">Оберіть параметр</option>';
            items.forEach((entry) => {
                const option = document.createElement('option');
                option.value = entry.id;
                option.textContent = entry.value;
                attributeSelect.appendChild(option);
            });

            if (row) {
                updateCollapsibleSummary(row);
            }
        })
        .catch(() => {});
});

document.querySelectorAll('[data-collection-add]').forEach((button) => {
    button.addEventListener('click', () => {
        if (button.dataset.busy === '1') {
            return;
        }

        const holder = document.querySelector(button.dataset.collectionAdd);
        if (!holder || !holder.dataset.prototype) {
            return;
        }

        button.dataset.busy = '1';

        const usedIndexes = Array.from(holder.querySelectorAll('[name]')).map((el) => {
            const match = String(el.getAttribute('name') || '').match(/\[(\d+)\]/);
            return match ? Number(match[1]) : -1;
        });
        const index = usedIndexes.length
            ? Math.max(...usedIndexes) + 1
            : holder.querySelectorAll('.collection-item').length;
        const inner = holder.dataset.prototype.replace(/__name__/g, String(index));
        const collapsibleType = holder.dataset.collapsibleType;

        if (collapsibleType) {
            const html = wrapCollapsibleItem(inner, collapsibleType, false);
            holder.insertAdjacentHTML('beforeend', html);
            initCollapsibleItem(holder.lastElementChild);
        } else {
            const html = '<div class="collection-item">' +
                '<button type="button" class="btn btn-sm btn-outline-danger collection-item__remove" data-collection-remove>' +
                '<i class="bi bi-trash"></i></button>' + inner + '</div>';
            holder.insertAdjacentHTML('beforeend', html);
        }

        window.setTimeout(() => {
            button.dataset.busy = '';
        }, 300);
    });
});

document.addEventListener('click', (event) => {
    const target = event.target;
    if (!(target instanceof HTMLElement)) {
        return;
    }

    const toggle = target.closest('[data-collapsible-toggle]');
    if (toggle) {
        const item = toggle.closest('[data-collapsible]');
        if (!item) {
            return;
        }

        const collapsed = !item.classList.contains('is-collapsed');
        item.classList.toggle('is-collapsed', collapsed);
        toggle.setAttribute('aria-expanded', collapsed ? 'false' : 'true');

        if (collapsed) {
            updateCollapsibleSummary(item);
        }

        return;
    }

    const removeButton = target.closest('[data-collection-remove]');
    if (!removeButton) {
        return;
    }

    removeButton.closest('.collection-item')?.remove();
});
