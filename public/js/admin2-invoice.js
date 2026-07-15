(function () {
    const modalEl = document.getElementById('orderInvoiceModal');
    if (!modalEl) {
        return;
    }

    const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
    const errorBox = document.getElementById('orderInvoiceError');
    const csrfToken = modalEl.dataset.csrfToken || '';

    const fopSelect = document.getElementById('invoiceFopId');
    const receiverName = document.getElementById('invoiceReceiverName');
    const receiverReq = document.getElementById('invoiceReceiverReq');
    const generateBtn = document.getElementById('invoiceGenerateBtn');

    let currentOrderId = null;

    const showError = (message) => {
        errorBox.textContent = message;
        errorBox.classList.remove('d-none');
    };

    const clearError = () => {
        errorBox.textContent = '';
        errorBox.classList.add('d-none');
    };

    async function loadFops() {
        const response = await fetch('/admin2/invoices/fops', { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
        const data = await response.json().catch(() => ({}));
        if (!response.ok) {
            throw new Error(data.error || 'Не вдалося завантажити ФОП');
        }

        const items = Array.isArray(data.items) ? data.items : [];
        fopSelect.innerHTML = '';
        items.forEach((item) => {
            const opt = document.createElement('option');
            opt.value = String(item.id);
            opt.textContent = item.title || `#${item.id}`;
            fopSelect.appendChild(opt);
        });

        if (!items.length) {
            const opt = document.createElement('option');
            opt.value = '';
            opt.textContent = 'Немає ФОП (додайте в Налаштуваннях)';
            fopSelect.appendChild(opt);
        }
    }

    document.querySelectorAll('.order-invoice-btn').forEach((btn) => {
        btn.addEventListener('click', async () => {
            clearError();
            currentOrderId = btn.dataset.orderId || null;
            if (!currentOrderId) {
                return;
            }

            generateBtn.disabled = true;
            try {
                await loadFops();
                modal.show();
            } catch (e) {
                showError(e.message || 'Помилка');
                modal.show();
            } finally {
                generateBtn.disabled = false;
            }
        });
    });

    generateBtn?.addEventListener('click', async () => {
        clearError();
        if (!currentOrderId) {
            return;
        }

        const payload = {
            fopId: Number(fopSelect.value || 0),
            receiverName: receiverName.value || '',
            receiverRequisites: receiverReq.value || '',
        };

        generateBtn.disabled = true;
        try {
            const response = await fetch(`/admin2/orders/${currentOrderId}/invoice`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': csrfToken,
                },
                body: JSON.stringify(payload),
            });

            if (!response.ok) {
                const data = await response.json().catch(() => ({}));
                throw new Error(data.error || 'Не вдалося згенерувати документи');
            }

            const blob = await response.blob();
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `invoice_${currentOrderId}.zip`;
            document.body.appendChild(a);
            a.click();
            a.remove();
            window.setTimeout(() => URL.revokeObjectURL(url), 60_000);
            modal.hide();
        } catch (e) {
            showError(e.message || 'Помилка');
        } finally {
            generateBtn.disabled = false;
        }
    });
})();

