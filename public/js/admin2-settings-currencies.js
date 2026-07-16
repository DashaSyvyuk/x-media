document.querySelectorAll('.js-edit-currency').forEach((button) => {
    button.addEventListener('click', () => {
        const form = document.getElementById('editCurrencyForm');
        if (!form) {
            return;
        }

        form.action = `/admin/settings/currencies/${button.dataset.id}`;
        document.getElementById('editCurrencyTitle').value = button.dataset.title || '';
        document.getElementById('editCurrencyShortTitle').value = button.dataset.shortTitle || '';
        document.getElementById('editCurrencyCode').value = button.dataset.code || '';
        document.getElementById('editCurrencyRate').value = button.dataset.rate || '0';
    });
});
