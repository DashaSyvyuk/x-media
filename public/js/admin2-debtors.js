const editPaymentModal = document.getElementById('editPaymentModal');

if (editPaymentModal) {
    editPaymentModal.addEventListener('show.bs.modal', (event) => {
        const button = event.relatedTarget;
        if (!button) {
            return;
        }

        const form = document.getElementById('editPaymentForm');
        if (form && button.dataset.action) {
            form.action = button.dataset.action;
        }

        const sumField = document.getElementById('editPaymentSum');
        const noteField = document.getElementById('editPaymentNote');

        if (sumField) {
            sumField.value = button.dataset.sum || '';
        }

        if (noteField) {
            noteField.value = button.dataset.note || '';
        }
    });
}
