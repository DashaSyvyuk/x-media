(function () {
    const modalEl = document.getElementById('orderReceiptModal');
    if (!modalEl) {
        return;
    }

    const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
    const form = document.getElementById('orderReceiptForm');
    const itemsBody = document.getElementById('receiptItemsBody');
    const errorBox = document.getElementById('orderReceiptError');
    const csrfToken = modalEl.dataset.csrfToken || '';
    let wordsRequestTimer = null;

    const showError = (message) => {
        errorBox.textContent = message;
        errorBox.classList.remove('d-none');
    };

    const clearError = () => {
        errorBox.textContent = '';
        errorBox.classList.add('d-none');
    };

    const formatRowSum = (row) => {
        const price = parseInt(row.querySelector('.receipt-item-price')?.value || '0', 10) || 0;
        const qty = parseInt(row.querySelector('.receipt-item-qty')?.value || '1', 10) || 1;
        const sumInput = row.querySelector('.receipt-item-sum');
        if (sumInput) {
            sumInput.value = String(price * qty);
        }
    };

    const recalculateTotal = () => {
        let total = 0;
        itemsBody.querySelectorAll('tr').forEach((row) => {
            formatRowSum(row);
            total += parseInt(row.querySelector('.receipt-item-sum')?.value || '0', 10) || 0;
        });
        document.getElementById('receiptTotal').value = String(total);
        scheduleWordsUpdate(total);
    };

    const scheduleWordsUpdate = (amount) => {
        window.clearTimeout(wordsRequestTimer);
        wordsRequestTimer = window.setTimeout(async () => {
            try {
                const response = await fetch(`/admin2/receipts/amount-words?amount=${amount}`);
                const data = await response.json();
                if (data.totalWords) {
                    document.getElementById('receiptTotalWords').value = data.totalWords;
                }
            } catch {
                // keep manual value
            }
        }, 250);
    };

    const createItemRow = (item, index) => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td><input type="number" class="form-control receipt-item-no" min="1" value="${item.no ?? index + 1}"></td>
            <td><input type="text" class="form-control receipt-item-name" value="${escapeHtml(item.name ?? '')}"></td>
            <td><input type="number" class="form-control receipt-item-warranty" min="0" value="${item.warranty ?? 24}"></td>
            <td><input type="number" class="form-control receipt-item-qty" min="1" value="${item.qty ?? 1}"></td>
            <td><input type="number" class="form-control receipt-item-price" min="0" step="1" value="${item.price ?? 0}"></td>
            <td><input type="number" class="form-control receipt-item-sum" min="0" step="1" value="${item.sum ?? 0}"></td>
            <td><button type="button" class="btn btn-sm btn-outline-danger receipt-remove-item" title="Видалити"><i class="bi bi-trash"></i></button></td>
        `;
        row.querySelectorAll('.receipt-item-price, .receipt-item-qty').forEach((input) => {
            input.addEventListener('input', recalculateTotal);
        });
        row.querySelector('.receipt-remove-item')?.addEventListener('click', () => {
            row.remove();
            recalculateTotal();
        });
        return row;
    };

    const escapeHtml = (value) => String(value)
        .replace(/&/g, '&amp;')
        .replace(/"/g, '&quot;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;');

    const populateForm = (payload) => {
        document.getElementById('receiptTemplate').value = payload.template || 'xmedia';
        document.getElementById('receiptFilename').value = payload.filename || 'receipt';
        document.getElementById('receiptCheckNumber').value = payload.checkNumber || '';
        document.getElementById('receiptDateDay').value = payload.dateDay || '';
        document.getElementById('receiptDateMonth').value = payload.dateMonth || '';
        document.getElementById('receiptDateYear').value = payload.dateYear || '';
        document.getElementById('receiptTotal').value = String(payload.total ?? 0);
        document.getElementById('receiptTotalWords').value = payload.totalWords || '';

        itemsBody.innerHTML = '';
        (payload.items || []).forEach((item, index) => {
            itemsBody.appendChild(createItemRow(item, index));
        });
        if (!itemsBody.children.length) {
            itemsBody.appendChild(createItemRow({}, 0));
        }
    };

    const collectPayload = () => {
        const items = [];
        itemsBody.querySelectorAll('tr').forEach((row, index) => {
            items.push({
                no: parseInt(row.querySelector('.receipt-item-no')?.value || String(index + 1), 10) || (index + 1),
                name: row.querySelector('.receipt-item-name')?.value || '',
                warranty: parseInt(row.querySelector('.receipt-item-warranty')?.value || '24', 10) || 24,
                qty: parseInt(row.querySelector('.receipt-item-qty')?.value || '1', 10) || 1,
                price: parseInt(row.querySelector('.receipt-item-price')?.value || '0', 10) || 0,
                sum: parseInt(row.querySelector('.receipt-item-sum')?.value || '0', 10) || 0,
            });
        });

        return {
            template: document.getElementById('receiptTemplate').value,
            filename: document.getElementById('receiptFilename').value,
            checkNumber: document.getElementById('receiptCheckNumber').value,
            dateDay: document.getElementById('receiptDateDay').value,
            dateMonth: document.getElementById('receiptDateMonth').value,
            dateYear: document.getElementById('receiptDateYear').value,
            total: parseInt(document.getElementById('receiptTotal').value || '0', 10) || 0,
            totalWords: document.getElementById('receiptTotalWords').value,
            items,
        };
    };

    const requestReceipt = async () => {
        const response = await fetch('/admin2/receipts/generate', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': csrfToken,
            },
            body: JSON.stringify(collectPayload()),
        });

        if (!response.ok) {
            let message = 'Не вдалося сформувати чек';
            try {
                const data = await response.json();
                message = data.error || message;
            } catch {
                // ignore
            }
            throw new Error(message);
        }

        return response.blob();
    };

    const downloadReceiptBlob = (blob, filename) => {
        const url = URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.download = `${filename}.docx`;
        document.body.appendChild(link);
        link.click();
        link.remove();
        window.setTimeout(() => URL.revokeObjectURL(url), 60_000);
    };

    document.querySelectorAll('.order-receipt-btn').forEach((button) => {
        button.addEventListener('click', async () => {
            clearError();
            const type = button.dataset.receiptType;
            const id = button.dataset.receiptId;
            if (!type || !id) {
                return;
            }

            button.disabled = true;
            try {
                const response = await fetch(`/admin2/receipts/data/${type}/${id}`);
                const data = await response.json();
                if (!response.ok) {
                    throw new Error(data.error || 'Не вдалося завантажити дані чека');
                }
                populateForm(data);
                modal.show();
            } catch (error) {
                showError(error.message || 'Помилка завантаження');
                modal.show();
            } finally {
                button.disabled = false;
            }
        });
    });

    document.getElementById('receiptAddItem')?.addEventListener('click', () => {
        const index = itemsBody.querySelectorAll('tr').length;
        itemsBody.appendChild(createItemRow({ no: index + 1, warranty: 24, qty: 1 }, index));
        recalculateTotal();
    });

    document.getElementById('receiptTotal')?.addEventListener('input', (event) => {
        scheduleWordsUpdate(parseInt(event.target.value || '0', 10) || 0);
    });

    document.getElementById('receiptSaveBtn')?.addEventListener('click', async () => {
        clearError();
        try {
            const blob = await requestReceipt();
            downloadReceiptBlob(blob, document.getElementById('receiptFilename').value || 'receipt');
        } catch (error) {
            showError(error.message || 'Помилка збереження');
        }
    });
})();
