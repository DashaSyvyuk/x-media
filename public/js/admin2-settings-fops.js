document.querySelectorAll('.js-edit-fop').forEach((button) => {
    button.addEventListener('click', () => {
        const form = document.getElementById('editFopForm');
        if (!form) {
            return;
        }

        form.action = `/admin/settings/fops/${button.dataset.id}`;
        document.getElementById('editFopTitle').value = button.dataset.title || '';
        document.getElementById('editFopEdrpou').value = button.dataset.edrpou || '';
        document.getElementById('editFopBankAccount').value = button.dataset.bankAccount || '';
        document.getElementById('editFopAddress').value = button.dataset.address || '';
        document.getElementById('editFopNote').value = button.dataset.note || '';
    });
});

