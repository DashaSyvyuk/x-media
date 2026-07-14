document.querySelectorAll('.js-edit-shop-setting').forEach((button) => {
    button.addEventListener('click', () => {
        const form = document.getElementById('editShopSettingForm');
        if (!form) {
            return;
        }

        form.action = `/admin2/settings/shop-settings/${button.dataset.id}`;
        document.getElementById('editShopSettingTitle').value = button.dataset.title || '';
        document.getElementById('editShopSettingSlug').value = button.dataset.slug || '';
        document.getElementById('editShopSettingValue').value = button.dataset.value || '';
    });
});
