document.querySelectorAll('.js-edit-payment-type').forEach((button) => {
    button.addEventListener('click', () => {
        const form = document.getElementById('editPaymentTypeForm');
        if (!form) {
            return;
        }

        form.action = `/admin2/settings/payment-types/${button.dataset.id}`;
        document.getElementById('editPaymentTypeTitle').value = button.dataset.title || '';
        document.getElementById('editPaymentTypeCost').value = button.dataset.cost || '0';
        document.getElementById('editPaymentTypeEnabled').checked = button.dataset.enabled === '1';

        const iconWrap = document.getElementById('editPaymentTypeIconWrap');
        const icon = document.getElementById('editPaymentTypeIcon');
        if (button.dataset.icon) {
            icon.src = button.dataset.icon;
            iconWrap.hidden = false;
        } else {
            iconWrap.hidden = true;
        }
    });
});
