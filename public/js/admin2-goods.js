(function () {
    const syncSaleField = (modal) => {
        if (!modal) {
            return;
        }

        const soldToggle = modal.querySelector('.js-planning-good-sold-toggle');
        const saleField = modal.querySelector('.js-sale-field');
        const saleInput = modal.querySelector('[name="sale_price"]');
        if (!soldToggle || !saleInput) {
            return;
        }

        const update = () => {
            const isSold = soldToggle.checked;
            saleInput.required = isSold;
            saleField?.classList.toggle('opacity-50', !isSold);
        };

        soldToggle.addEventListener('change', update);
        update();
    };

    document.querySelectorAll('.planning-goods-blocks').forEach((container) => {
        container.addEventListener('click', (event) => {
            const toggle = event.target.closest('[data-goods-block-toggle]');
            if (!toggle) {
                return;
            }

            const block = toggle.closest('.planning-goods-block');
            if (!block) {
                return;
            }

            const isOpen = block.classList.toggle('is-open');
            toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        });
    });

    const createGoodModal = document.getElementById('createPlanningGoodModal');
    const editGoodModal = document.getElementById('editPlanningGoodModal');
    const deleteGoodModal = document.getElementById('deletePlanningGoodModal');
    const editBatchModal = document.getElementById('editPlanningGoodBatchModal');
    const deleteBatchModal = document.getElementById('deletePlanningGoodBatchModal');

    syncSaleField(createGoodModal);
    syncSaleField(editGoodModal);

    createGoodModal?.addEventListener('show.bs.modal', (event) => {
        const button = event.relatedTarget;
        const batchSelect = document.getElementById('createPlanningGoodBatchId');
        if (button?.dataset.batchId && batchSelect) {
            batchSelect.value = button.dataset.batchId;
        }
    });

    editGoodModal?.addEventListener('show.bs.modal', (event) => {
        const button = event.relatedTarget;
        if (!button) {
            return;
        }

        document.getElementById('editPlanningGoodForm').action = `/admin/goods/${button.dataset.id}`;
        document.getElementById('editPlanningGoodName').value = button.dataset.name || '';
        document.getElementById('editPlanningGoodPurchasePrice').value = button.dataset.purchasePrice || '';
        document.getElementById('editPlanningGoodDeliveryPrice').value = button.dataset.deliveryPrice || '0';
        document.getElementById('editPlanningGoodSalePrice').value = button.dataset.salePrice || '';
        document.getElementById('editPlanningGoodBatchId').value = button.dataset.batchId || '';
        document.getElementById('editPlanningGoodWarehouseId').value = button.dataset.warehouseId || '';

        const soldToggle = document.getElementById('editPlanningGoodIsSold');
        soldToggle.checked = button.dataset.isSold === '1';
        soldToggle.dispatchEvent(new Event('change'));
    });

    deleteGoodModal?.addEventListener('show.bs.modal', (event) => {
        const button = event.relatedTarget;
        if (!button) {
            return;
        }

        document.getElementById('deletePlanningGoodForm').action = `/admin/goods/${button.dataset.id}/delete`;
        document.getElementById('deletePlanningGoodName').textContent = button.dataset.name || '';
    });

    editBatchModal?.addEventListener('show.bs.modal', (event) => {
        const button = event.relatedTarget;
        if (!button) {
            return;
        }

        document.getElementById('editPlanningGoodBatchForm').action = `/admin/goods/batches/${button.dataset.id}`;
        document.getElementById('editPlanningGoodBatchName').value = button.dataset.name || '';
        document.getElementById('editPlanningGoodBatchDate').value = button.dataset.recordedDate || '';
    });

    deleteBatchModal?.addEventListener('show.bs.modal', (event) => {
        const button = event.relatedTarget;
        if (!button) {
            return;
        }

        document.getElementById('deletePlanningGoodBatchForm').action = `/admin/goods/batches/${button.dataset.id}/delete`;
        document.getElementById('deletePlanningGoodBatchName').textContent = button.dataset.name || '';
    });
})();
