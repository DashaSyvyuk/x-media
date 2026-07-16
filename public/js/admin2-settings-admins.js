document.querySelectorAll('.js-edit-admin-user').forEach((button) => {
    button.addEventListener('click', () => {
        const form = document.getElementById('editAdminUserForm');
        if (!form) {
            return;
        }

        form.action = `/admin/settings/admins/${button.dataset.id}`;
        document.getElementById('editAdminUserEmail').value = button.dataset.email || '';
        document.getElementById('editAdminUserName').value = button.dataset.name || '';
        document.getElementById('editAdminUserSurname').value = button.dataset.surname || '';
        document.getElementById('editAdminUserPhone').value = button.dataset.phone || '';
        document.getElementById('editAdminUserActive').checked = button.dataset.active === '1';

        let roles = [];
        try {
            roles = JSON.parse(button.dataset.roles || '[]');
        } catch (error) {
            roles = [];
        }

        document.querySelectorAll('.js-edit-admin-role').forEach((checkbox) => {
            checkbox.checked = roles.includes(checkbox.value);
        });
    });
});
