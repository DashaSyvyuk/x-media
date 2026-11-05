(function () {
    document.querySelectorAll('.js-edit-plan').forEach((button) => {
        button.addEventListener('click', () => {
            const form = document.getElementById('editPlanForm');
            if (!form) {
                return;
            }

            form.action = '/admin/planning/' + button.dataset.id;
            document.getElementById('editPlanBoard').value = button.dataset.board || '';
            document.getElementById('editPlanDate').value = button.dataset.date || '';
            document.getElementById('editPlanStatus').value = button.dataset.status || 'new';
            document.getElementById('editPlanTitle').value = button.dataset.title || '';
            document.getElementById('editPlanBody').value = button.dataset.body || '';

            const assigneeSelect = document.getElementById('editPlanAssignee');
            const assigneeId = button.dataset.assigneeId || '';
            if (assigneeId && !assigneeSelect.querySelector('option[value="' + assigneeId + '"]')) {
                const option = document.createElement('option');
                option.value = assigneeId;
                option.textContent = button.dataset.assigneeName || ('#' + assigneeId);
                assigneeSelect.appendChild(option);
            }
            assigneeSelect.value = assigneeId;
        });
    });

    document.querySelectorAll('.js-edit-board').forEach((button) => {
        button.addEventListener('click', () => {
            const form = document.getElementById('editBoardForm');
            const deleteBtn = document.getElementById('deleteBoardBtn');
            if (!form) {
                return;
            }

            const id = button.dataset.id;
            form.action = '/admin/planning/boards/' + id;
            if (deleteBtn) {
                deleteBtn.setAttribute('formaction', '/admin/planning/boards/' + id + '/delete');
            }
            document.getElementById('editBoardTitle').value = button.dataset.title || '';
            const color = button.dataset.color || 'teal';
            form.querySelectorAll('input[name="color"]').forEach((input) => {
                input.checked = input.value === color;
            });
        });
    });

    document.addEventListener('submit', (event) => {
        const submitter = event.submitter;
        if (!submitter || !submitter.hasAttribute('data-confirm')) {
            return;
        }
        if (!window.confirm(submitter.dataset.confirm)) {
            event.preventDefault();
        }
    });
})();
