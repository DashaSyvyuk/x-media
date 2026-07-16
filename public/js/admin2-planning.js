(function () {
    document.querySelectorAll('[data-planning-tab]').forEach((button) => {
        button.addEventListener('click', () => {
            const tab = button.dataset.planningTab;
            document.querySelectorAll('[data-planning-tab]').forEach((item) => {
                const active = item === button;
                item.classList.toggle('is-active', active);
                item.setAttribute('aria-selected', active ? 'true' : 'false');
            });
            document.querySelectorAll('[data-planning-panel]').forEach((panel) => {
                panel.classList.toggle('is-active', panel.dataset.planningPanel === tab);
            });
        });
    });

    document.querySelectorAll('.js-edit-plan').forEach((button) => {
        button.addEventListener('click', () => {
            const form = document.getElementById('editPlanForm');
            if (!form) {
                return;
            }

            form.action = '/admin/planning/' + button.dataset.id;
            document.getElementById('editPlanDate').value = button.dataset.date || '';
            const assigneeSelect = document.getElementById('editPlanAssignee');
            const assigneeId = button.dataset.assigneeId || '';
            if (assigneeId && !assigneeSelect.querySelector('option[value="' + assigneeId + '"]')) {
                const option = document.createElement('option');
                option.value = assigneeId;
                option.textContent = button.dataset.assigneeName || ('#' + assigneeId);
                assigneeSelect.appendChild(option);
            }
            assigneeSelect.value = assigneeId;
            document.getElementById('editPlanTitle').value = button.dataset.title || '';
            document.getElementById('editPlanBody').value = button.dataset.body || '';
            document.getElementById('editPlanShow').value = button.dataset.show || '';
        });
    });

    document.querySelectorAll('[data-confirm]').forEach((button) => {
        button.closest('form')?.addEventListener('submit', (event) => {
            if (!window.confirm(button.dataset.confirm)) {
                event.preventDefault();
            }
        });
    });
})();
