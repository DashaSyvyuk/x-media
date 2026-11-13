(function () {
    const kanban = document.querySelector('.planning-kanban');

    const parseJsonAttr = (el, name, fallback) => {
        if (!el) {
            return fallback;
        }
        try {
            return JSON.parse(el.getAttribute(name) || '') || fallback;
        } catch {
            return fallback;
        }
    };

    const columnsMeta = parseJsonAttr(kanban, 'data-columns', {});

    const postForm = async (url, form) => {
        const response = await fetch(url, {
            method: 'POST',
            body: new FormData(form),
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
        });

        let payload = {};
        try {
            payload = await response.json();
        } catch {
            payload = { ok: false, message: 'Невірна відповідь сервера' };
        }

        if (!response.ok || payload.ok === false) {
            throw new Error(payload.message || 'Помилка запиту');
        }

        return payload;
    };

    const columnCards = (column) => column?.querySelector('.planning-column__cards') || null;

    const columnPlanCards = (column) => {
        const list = columnCards(column);
        return list ? [...list.querySelectorAll(':scope > .plan-card')] : [];
    };

    const refreshColumnUi = (column) => {
        if (!column) {
            return;
        }

        const list = columnCards(column);
        if (!list) {
            return;
        }

        const cards = columnPlanCards(column);
        const countEl = column.querySelector('.planning-column__count');
        if (countEl) {
            countEl.textContent = String(cards.length);
        }

        let empty = list.querySelector(':scope > .planning-column__empty');
        if (cards.length === 0) {
            if (!empty) {
                empty = document.createElement('div');
                empty.className = 'planning-column__empty';
                empty.textContent = 'Порожньо';
                list.appendChild(empty);
            }
        } else if (empty) {
            empty.remove();
        }

        cards.forEach((card, index) => {
            const upBtn = card.querySelector('.js-plan-move input[value="up"]')
                ?.closest('form')
                ?.querySelector('.plan-card__arrow');
            const downBtn = card.querySelector('.js-plan-move input[value="down"]')
                ?.closest('form')
                ?.querySelector('.plan-card__arrow');
            if (upBtn) {
                upBtn.disabled = index === 0;
            }
            if (downBtn) {
                downBtn.disabled = index === cards.length - 1;
            }
        });
    };

    const rebuildStatusMoves = (card, currentColumnId) => {
        const wrap = card.querySelector('.plan-card__status-moves');
        if (!wrap || !kanban) {
            return;
        }

        const csrf = kanban.dataset.csrf || '';
        const boardInput = card.querySelector(
            '.js-plan-status input[name="board"], .js-plan-move input[name="board"]',
        );
        const boardId = boardInput ? boardInput.value : '';
        const planId = card.dataset.planId;
        wrap.innerHTML = '';

        Object.keys(columnsMeta).forEach((columnId) => {
            if (String(columnId) === String(currentColumnId)) {
                return;
            }
            const meta = columnsMeta[columnId];
            const form = document.createElement('form');
            form.method = 'post';
            form.action = '/admin/planning/' + planId + '/status';
            form.className = 'd-inline js-plan-status';

            const token = document.createElement('input');
            token.type = 'hidden';
            token.name = '_token';
            token.value = csrf;
            form.appendChild(token);

            const board = document.createElement('input');
            board.type = 'hidden';
            board.name = 'board';
            board.value = boardId;
            form.appendChild(board);

            const statusInput = document.createElement('input');
            statusInput.type = 'hidden';
            statusInput.name = 'status';
            statusInput.value = columnId;
            form.appendChild(statusInput);

            const button = document.createElement('button');
            button.type = 'submit';
            button.className = 'plan-card__move-btn';
            button.style.setProperty('--move-color', meta.colorHex || '#64748b');
            button.title = '→ ' + meta.title;
            button.textContent = meta.title;
            form.appendChild(button);

            wrap.appendChild(form);
        });
    };

    const applyCardColumn = (card, columnId, statusLabel, statusColor, isDone) => {
        card.dataset.status = String(columnId);
        card.dataset.columnId = String(columnId);
        card.classList.toggle('plan-card--completed', !!isDone);

        const badge = card.querySelector('.plan-card__status');
        if (badge) {
            badge.textContent = statusLabel;
            badge.style.setProperty('--status-color', statusColor);
        }

        const editBtn = card.querySelector('.js-edit-plan');
        if (editBtn) {
            editBtn.dataset.status = String(columnId);
        }

        rebuildStatusMoves(card, columnId);
    };

    const setBusy = (form, busy) => {
        form.querySelectorAll('button').forEach((button) => {
            button.disabled = busy;
        });
    };

    document.querySelectorAll('.js-edit-plan').forEach((button) => {
        button.addEventListener('click', () => {
            const form = document.getElementById('editPlanForm');
            if (!form) {
                return;
            }

            form.action = '/admin/planning/' + button.dataset.id;
            document.getElementById('editPlanBoard').value = button.dataset.board || '';
            document.getElementById('editPlanDate').value = button.dataset.date || '';
            document.getElementById('editPlanStatus').value = button.dataset.status || '';
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

    const fillColumnEditForm = (button) => {
        const form = document.getElementById('editColumnForm');
        if (!form || !button) {
            return;
        }

        const id = button.dataset.id;
        if (!id) {
            return;
        }

        form.action = '/admin/planning/columns/' + id;
        document.getElementById('editColumnTitle').value = button.dataset.title || '';
        document.getElementById('editColumnDone').checked = button.dataset.done === '1';
        const color = button.dataset.color || 'blue';
        form.querySelectorAll('input[name="color"]').forEach((input) => {
            input.checked = input.value === color;
        });
    };

    document.querySelectorAll('.js-edit-column').forEach((button) => {
        button.addEventListener('click', () => fillColumnEditForm(button));
    });

    const editColumnModal = document.getElementById('editColumnModal');
    if (editColumnModal) {
        editColumnModal.addEventListener('show.bs.modal', (event) => {
            const button = event.relatedTarget;
            if (button && button.classList.contains('js-edit-column')) {
                fillColumnEditForm(button);
            }
        });
    }

    const editColumnForm = document.getElementById('editColumnForm');
    if (editColumnForm) {
        editColumnForm.addEventListener('submit', (event) => {
            if (!editColumnForm.action || editColumnForm.action.endsWith('/columns/0')) {
                event.preventDefault();
                window.alert('Спочатку оберіть стовпець для редагування.');
            }
        });
    }

    document.addEventListener('submit', (event) => {
        const form = event.target;
        if (!(form instanceof HTMLFormElement)) {
            return;
        }

        const submitter = event.submitter;
        if (submitter && submitter.hasAttribute('data-confirm')) {
            if (!window.confirm(submitter.dataset.confirm)) {
                event.preventDefault();
                return;
            }
        }

        const isMove = form.classList.contains('js-plan-move');
        const isStatus = form.classList.contains('js-plan-status');
        if (!isMove && !isStatus) {
            return;
        }

        event.preventDefault();
        if (form.dataset.busy === '1') {
            return;
        }

        const card = form.closest('.plan-card');
        if (!card) {
            return;
        }

        form.dataset.busy = '1';
        setBusy(form, true);

        postForm(form.action, form)
            .then((payload) => {
                if (isMove) {
                    if (!payload.moved) {
                        return;
                    }
                    const column = card.closest('.planning-column');
                    const list = columnCards(column);
                    if (!list) {
                        return;
                    }
                    const direction = form.querySelector('input[name="direction"]')?.value;
                    if (direction === 'up') {
                        const prev = card.previousElementSibling;
                        if (prev && prev.classList.contains('plan-card')) {
                            list.insertBefore(card, prev);
                        }
                    } else if (direction === 'down') {
                        const next = card.nextElementSibling;
                        if (next && next.classList.contains('plan-card')) {
                            list.insertBefore(next, card);
                        }
                    }
                    refreshColumnUi(column);
                    return;
                }

                const status = String(payload.status);
                const targetColumn = kanban?.querySelector(
                    '.planning-column[data-status="' + status + '"]',
                );
                const sourceColumn = card.closest('.planning-column');
                const targetList = columnCards(targetColumn);
                if (!targetList) {
                    return;
                }

                const meta = columnsMeta[status] || {};
                applyCardColumn(
                    card,
                    status,
                    payload.statusLabel || meta.title || status,
                    payload.statusColor || meta.colorHex || '#64748b',
                    payload.isDone ?? meta.isDone,
                );

                targetList.appendChild(card);
                refreshColumnUi(sourceColumn);
                refreshColumnUi(targetColumn);
            })
            .catch((error) => {
                window.alert(error.message || 'Не вдалося перемістити картку');
            })
            .finally(() => {
                form.dataset.busy = '0';
                setBusy(form, false);
                const column = card.closest('.planning-column');
                refreshColumnUi(column);
            });
    });
})();
