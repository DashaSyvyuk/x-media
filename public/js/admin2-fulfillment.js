(function () {
    const board = document.querySelector('.fulfillment-board');
    if (!board) {
        return;
    }

    const isManagementBoard = Boolean(board.querySelector('.fulfillment-card--vendor'));
    const unlinkUrl = board.dataset.unlinkUrl || '/admin/fulfillment/unlink';
    const csrfToken = board.dataset.csrfToken || '';

    let peerMap = {};
    try {
        peerMap = JSON.parse(board.dataset.linkPeers || '{}');
    } catch {
        peerMap = {};
    }

    const cardsByKey = new Map();
    const refreshCardIndex = () => {
        cardsByKey.clear();
        document.querySelectorAll('.fulfillment-card[data-card-key]').forEach((card) => {
            cardsByKey.set(card.dataset.cardKey, card);
        });
    };
    refreshCardIndex();

    const toast = (() => {
        let el = document.getElementById('fulfillmentToast');
        if (!el) {
            el = document.createElement('div');
            el.id = 'fulfillmentToast';
            el.className = 'fulfillment-toast';
            el.setAttribute('aria-live', 'polite');
            document.body.appendChild(el);
        }
        let timer = null;
        return (message, type = 'success') => {
            if (!message) {
                return;
            }
            el.textContent = message;
            el.className = `fulfillment-toast fulfillment-toast--${type} is-visible`;
            window.clearTimeout(timer);
            timer = window.setTimeout(() => {
                el.classList.remove('is-visible');
            }, 2800);
        };
    })();

    const postForm = async (url, formOrData) => {
        const body = formOrData instanceof FormData
            ? formOrData
            : new FormData(formOrData);

        const response = await fetch(url, {
            method: 'POST',
            body,
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

    const applyLinkColors = (colors) => {
        const map = colors && typeof colors === 'object' ? colors : {};
        document.querySelectorAll('.fulfillment-card[data-card-key]').forEach((card) => {
            const key = card.dataset.cardKey || '';
            const color = map[key] || '';
            let dot = card.querySelector('.fulfillment-card__link-dot');

            if (color) {
                card.style.setProperty('--link-color', color);
                if (!dot) {
                    dot = document.createElement('span');
                    dot.className = 'fulfillment-card__link-dot';
                    card.prepend(dot);
                }
                dot.style.background = color;
            } else {
                card.style.removeProperty('--link-color');
                dot?.remove();
            }
        });
    };

    const applyBoardState = (boardState) => {
        if (!boardState) {
            return;
        }
        if (boardState.linkPeers) {
            peerMap = boardState.linkPeers;
            board.dataset.linkPeers = JSON.stringify(peerMap);
        }
        if (boardState.linkColors) {
            applyLinkColors(boardState.linkColors);
        }
        refreshCardIndex();
    };

    const updateColumnBadge = (columnEl) => {
        if (!columnEl) {
            return;
        }
        const badge = columnEl.querySelector('.fulfillment-column__header .badge');
        const count = columnEl.querySelectorAll('.fulfillment-card').length;
        if (badge) {
            badge.textContent = String(count);
        }
    };

    const findCustomerCard = (type, id) => document.querySelector(
        `.fulfillment-card[data-customer-type="${type}"][data-customer-id="${id}"]:not(.fulfillment-card--vendor)`,
    );

    const findVendorCard = (id) => document.querySelector(
        `.fulfillment-card--vendor[data-vendor-id="${id}"]`,
    );

    const setCustomerTone = (card, tone) => {
        if (!card || !tone) {
            return;
        }
        [...card.classList].forEach((cls) => {
            if (cls.startsWith('fulfillment-card--tone-')) {
                card.classList.remove(cls);
            }
        });
        card.classList.add(`fulfillment-card--tone-${tone}`);
    };

    const applyCustomerState = (customer) => {
        if (!customer?.type || !customer?.id) {
            return;
        }

        const card = findCustomerCard(customer.type, customer.id);
        if (!card) {
            return;
        }

        if (customer.statusTone) {
            setCustomerTone(card, customer.statusTone);
        }

        const statusSelect = card.querySelector('.fulfillment-status-select');
        if (statusSelect && !statusSelect.disabled) {
            if (customer.type === 'local' && customer.statusChoices) {
                const current = customer.statusCode || statusSelect.value;
                statusSelect.innerHTML = Object.entries(customer.statusChoices)
                    .map(([code, label]) => (
                        `<option value="${escapeHtml(code)}" ${code === current ? 'selected' : ''}>${escapeHtml(label)}</option>`
                    ))
                    .join('');
                if (![...statusSelect.options].some((opt) => opt.value === current) && current) {
                    const opt = document.createElement('option');
                    opt.value = current;
                    opt.textContent = customer.status || current;
                    opt.selected = true;
                    statusSelect.appendChild(opt);
                }
            } else if (customer.type === 'local' && customer.statusCode) {
                statusSelect.value = customer.statusCode;
            } else if (customer.type === 'rozetka' && customer.statusId != null) {
                statusSelect.value = String(customer.statusId);
            }
        }

        const statusLabel = card.querySelector('.fulfillment-card__status');
        if (statusLabel && customer.status) {
            statusLabel.textContent = customer.status;
        }

        const ttnInput = card.querySelector('.fulfillment-ttn-input');
        if (ttnInput && Object.prototype.hasOwnProperty.call(customer, 'ttn')) {
            ttnInput.value = customer.ttn || '';
            ttnInput.dataset.initialValue = ttnInput.value;
        }

        const hasTtn = Boolean(customer.hasTtn ?? (customer.ttn || '').trim());
        card.classList.toggle('fulfillment-card--has-ttn', hasTtn);

        // Management board only keeps new/processing — leave others until manual refresh.
        if (isManagementBoard
            && customer.statusTone
            && customer.statusTone !== 'new'
            && customer.statusTone !== 'processing'
        ) {
            // keep card; user asked not to re-sort / reshuffle immediately
        }
    };

    const escapeHtml = (text) => String(text)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');

    const ensureLinkList = (vendorCard) => {
        let formWrap = vendorCard.querySelector('.fulfillment-link-form');
        if (!formWrap) {
            formWrap = document.createElement('div');
            formWrap.className = 'fulfillment-link-form mt-2';
            vendorCard.appendChild(formWrap);
        }
        let list = formWrap.querySelector('.fulfillment-link-list');
        if (!list) {
            list = document.createElement('div');
            list.className = 'fulfillment-link-list';
            formWrap.prepend(list);
        }
        return list;
    };

    const ensureLinkSelect = (vendorCard) => {
        const formWrap = vendorCard.querySelector('.fulfillment-link-form') || vendorCard;
        let select = formWrap.querySelector('.fulfillment-link-target');
        if (!select) {
            select = document.createElement('select');
            select.className = 'form-select form-select-sm fulfillment-link-target';
            select.dataset.vendorId = vendorCard.dataset.vendorId || '';
            select.innerHTML = '<option value="">Пов\'язати з...</option>';
            formWrap.appendChild(select);
            bindLinkSelect(select);
        }
        return select;
    };

    const addLinkedCustomerRow = (vendorCard, linked) => {
        const list = ensureLinkList(vendorCard);
        if (list.querySelector(`[data-linked-value="${linked.value}"]`)) {
            return;
        }

        const row = document.createElement('div');
        row.className = 'fulfillment-link-current';
        row.dataset.linkedValue = linked.value;
        row.innerHTML = `
            <span class="small text-muted"><strong>${escapeHtml(linked.label)}</strong></span>
            <button type="button"
                    class="btn btn-sm btn-outline-danger fulfillment-unlink-btn"
                    title="Відв'язати"
                    data-vendor-id="${escapeHtml(String(linked.vendorId || vendorCard.dataset.vendorId || ''))}"
                    data-customer-type="${escapeHtml(linked.type)}"
                    data-customer-id="${escapeHtml(String(linked.id))}">
                <i class="bi bi-x-lg"></i>
            </button>
        `;
        list.appendChild(row);

        const select = vendorCard.querySelector('.fulfillment-link-target');
        if (select) {
            [...select.options].forEach((opt) => {
                if (opt.value === linked.value) {
                    opt.remove();
                }
            });
            select.querySelector('option[value=""]')?.replaceChildren(
                document.createTextNode('Додати прив\'язку...'),
            );
        }
    };

    const removeLinkedCustomerRow = (vendorCard, unlinked) => {
        const list = vendorCard.querySelector('.fulfillment-link-list');
        list?.querySelector(`[data-linked-value="${unlinked.value}"]`)?.remove();
        if (list && !list.children.length) {
            list.remove();
        }

        const select = ensureLinkSelect(vendorCard);
        if (![...select.options].some((opt) => opt.value === unlinked.value)) {
            const opt = document.createElement('option');
            opt.value = unlinked.value;
            opt.textContent = unlinked.label || unlinked.value;
            select.appendChild(opt);
        }
        const hasLinks = Boolean(vendorCard.querySelector('.fulfillment-link-current'));
        select.querySelector('option[value=""]')?.replaceChildren(
            document.createTextNode(hasLinks ? 'Додати прив\'язку...' : 'Пов\'язати з...'),
        );
        select.value = '';
    };

    const bindLinkSelect = (select) => {
        if (select.dataset.bound === '1') {
            return;
        }
        select.dataset.bound = '1';
        select.addEventListener('change', async () => {
            const value = select.value;
            if (!value || !value.includes(':')) {
                return;
            }

            const [customerType, customerId] = value.split(':');
            const form = document.getElementById('fulfillmentLinkForm');
            if (!form) {
                return;
            }

            const data = new FormData(form);
            data.set('vendor_order_id', select.dataset.vendorId || '');
            data.set('customer_type', customerType);
            data.set('customer_id', customerId);

            select.dataset.ajaxLock = '1';
            select.dataset.ajaxPending = '1';
            select.disabled = true;
            try {
                const payload = await postForm(form.action, data);
                const vendorCard = findVendorCard(select.dataset.vendorId);
                if (vendorCard && payload.linked) {
                    addLinkedCustomerRow(vendorCard, {
                        ...payload.linked,
                        vendorId: select.dataset.vendorId,
                    });
                }
                applyBoardState(payload.board);
                toast(payload.message || 'Пов\'язано');
            } catch (error) {
                toast(error.message || 'Не вдалося пов\'язати', 'error');
            } finally {
                delete select.dataset.ajaxPending;
                delete select.dataset.ajaxLock;
                select.disabled = false;
                select.value = '';
            }
        });
    };

    document.querySelectorAll('.fulfillment-link-target').forEach(bindLinkSelect);

    board.addEventListener('click', async (event) => {
        const unlinkBtn = event.target.closest('.fulfillment-unlink-btn');
        if (unlinkBtn) {
            event.preventDefault();
            const form = unlinkBtn.closest('form');
            const vendorId = unlinkBtn.dataset.vendorId
                || form?.querySelector('[name="vendor_order_id"]')?.value
                || '';
            const customerType = unlinkBtn.dataset.customerType
                || form?.querySelector('[name="customer_type"]')?.value
                || '';
            const customerId = unlinkBtn.dataset.customerId
                || form?.querySelector('[name="customer_id"]')?.value
                || '';

            const data = new FormData();
            data.set('_token', form?.querySelector('[name="_token"]')?.value || csrfToken);
            data.set('vendor_order_id', vendorId);
            data.set('customer_type', customerType);
            data.set('customer_id', customerId);

            unlinkBtn.disabled = true;
            try {
                const payload = await postForm(form?.action || unlinkUrl, data);
                const vendorCard = findVendorCard(vendorId);
                if (vendorCard && payload.unlinked) {
                    removeLinkedCustomerRow(vendorCard, payload.unlinked);
                }
                applyBoardState(payload.board);
                toast(payload.message || 'Прив\'язку знято');
            } catch (error) {
                toast(error.message || 'Не вдалося відв\'язати', 'error');
            } finally {
                unlinkBtn.disabled = false;
            }
            return;
        }

        const completeBtn = event.target.closest('.fulfillment-complete-btn');
        if (completeBtn) {
            event.preventDefault();
            const form = completeBtn.closest('form');
            if (!form) {
                return;
            }

            completeBtn.disabled = true;
            try {
                const payload = await postForm(form.action, form);
                const vendorCard = completeBtn.closest('.fulfillment-card--vendor');
                const column = vendorCard?.closest('.fulfillment-column');
                vendorCard?.remove();
                updateColumnBadge(column);
                (payload.updatedCustomers || []).forEach(applyCustomerState);
                applyBoardState(payload.board);
                refreshCardIndex();
                toast(payload.message || 'Закрито');
                (payload.warnings || []).forEach((warning) => toast(warning, 'error'));
            } catch (error) {
                toast(error.message || 'Не вдалося закрити', 'error');
            } finally {
                completeBtn.disabled = false;
            }
        }
    });

    const submitStatusForm = async (form) => {
        if (!form || form.dataset.submitting === '1') {
            return;
        }
        form.dataset.submitting = '1';
        try {
            const payload = await postForm(form.action, form);
            if (payload.customer) {
                applyCustomerState(payload.customer);
            }
            toast(payload.message || 'Оновлено');
        } catch (error) {
            toast(error.message || 'Не вдалося оновити', 'error');
        } finally {
            delete form.dataset.submitting;
        }
    };

    document.querySelectorAll('.fulfillment-status-select').forEach((select) => {
        select.addEventListener('change', () => {
            const form = select.closest('form');
            if (form && !select.disabled) {
                submitStatusForm(form);
            }
        });
    });

    document.querySelectorAll('.fulfillment-ttn-input').forEach((input) => {
        const submitTtn = () => {
            const form = input.closest('form');
            if (!form || input.disabled) {
                return;
            }
            if (input.value.trim() === (input.dataset.initialValue || '').trim()) {
                return;
            }
            submitStatusForm(form).then(() => {
                input.dataset.initialValue = input.value;
            });
        };

        input.dataset.initialValue = input.value;
        input.addEventListener('keydown', (event) => {
            if (event.key === 'Enter') {
                event.preventDefault();
                submitTtn();
            }
        });
        input.addEventListener('blur', submitTtn);
    });

    document.querySelectorAll('.fulfillment-status-form').forEach((form) => {
        form.addEventListener('submit', (event) => {
            event.preventDefault();
            submitStatusForm(form);
        });
    });

    document.querySelectorAll('form[action*="fulfillment/vendor/"][action*="/complete"]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            event.preventDefault();
        });
    });

    document.querySelectorAll('form[action*="fulfillment/unlink"]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            event.preventDefault();
        });
    });

    // Convert existing unlink forms' buttons to type=button handled above — keep as submit fallback
    document.querySelectorAll('.fulfillment-unlink-btn[type="submit"]').forEach((btn) => {
        const form = btn.closest('form');
        if (!form) {
            return;
        }
        btn.type = 'button';
        btn.dataset.vendorId = form.querySelector('[name="vendor_order_id"]')?.value || '';
        btn.dataset.customerType = form.querySelector('[name="customer_type"]')?.value || '';
        btn.dataset.customerId = form.querySelector('[name="customer_id"]')?.value || '';
    });

    document.querySelectorAll('.fulfillment-complete-btn[type="submit"]').forEach((btn) => {
        btn.type = 'button';
    });

    let activePeers = null;
    const clearHover = () => {
        document.querySelectorAll('.fulfillment-card--linked-hover').forEach((card) => {
            card.classList.remove('fulfillment-card--linked-hover');
        });
        board.classList.remove('fulfillment-board--link-hover');
        activePeers = null;
    };

    const isOverAnyPeer = (peers) => peers.some((key) => {
        const card = cardsByKey.get(key);
        return card && card.matches(':hover');
    });

    const setHover = (cardKey) => {
        const peers = peerMap[cardKey];
        if (!peers || peers.length < 2) {
            return;
        }
        clearHover();
        activePeers = peers;
        board.classList.add('fulfillment-board--link-hover');
        peers.forEach((key) => {
            cardsByKey.get(key)?.classList.add('fulfillment-card--linked-hover');
        });
    };

    const canHover = window.matchMedia('(hover: hover) and (pointer: fine)').matches;
    if (canHover) {
        board.addEventListener('mouseover', (event) => {
            const card = event.target.closest('.fulfillment-card[data-card-key]');
            if (card && board.contains(card)) {
                setHover(card.dataset.cardKey || '');
            }
        });
        board.addEventListener('mouseout', (event) => {
            const card = event.target.closest('.fulfillment-card[data-card-key]');
            if (!card) {
                return;
            }
            window.requestAnimationFrame(() => {
                if (!activePeers || !isOverAnyPeer(activePeers)) {
                    clearHover();
                }
            });
        });
    }

    const readBgMarks = (storageKey) => {
        try {
            const raw = localStorage.getItem(storageKey);
            const parsed = raw ? JSON.parse(raw) : {};
            return parsed && typeof parsed === 'object' ? parsed : {};
        } catch {
            return {};
        }
    };
    const writeBgMarks = (storageKey, marks) => {
        try {
            localStorage.setItem(storageKey, JSON.stringify(marks));
        } catch {
            // ignore
        }
    };

    const bindBgMarkToggle = (card, markId, storageKey, marks) => {
        const toggleBtn = card.querySelector('[data-bg-mark-toggle]');
        if (!markId || !toggleBtn || toggleBtn.dataset.bound === '1') {
            return;
        }
        toggleBtn.dataset.bound = '1';

        const applyMark = (on) => {
            card.classList.toggle('fulfillment-card--bg-mark', on);
            toggleBtn.classList.toggle('is-active', on);
            toggleBtn.setAttribute('aria-pressed', on ? 'true' : 'false');
            toggleBtn.title = on ? 'Повернути звичайний фон' : 'Змінити колір фону';
        };

        applyMark(Boolean(marks[markId]));
        toggleBtn.addEventListener('click', (event) => {
            event.preventDefault();
            event.stopPropagation();
            const next = !card.classList.contains('fulfillment-card--bg-mark');
            applyMark(next);
            if (next) {
                marks[markId] = 1;
            } else {
                delete marks[markId];
            }
            writeBgMarks(storageKey, marks);
        });
    };

    const vendorBgMarkKey = 'admin2.fulfillment.vendorBgMarks';
    const vendorBgMarks = readBgMarks(vendorBgMarkKey);
    document.querySelectorAll('.fulfillment-card--vendor[data-vendor-id]').forEach((card) => {
        bindBgMarkToggle(card, String(card.dataset.vendorId || ''), vendorBgMarkKey, vendorBgMarks);
    });

    const customerBgMarkKey = 'admin2.fulfillment.customerBgMarks';
    const customerBgMarks = readBgMarks(customerBgMarkKey);
    document.querySelectorAll('.fulfillment-card[data-customer-type][data-customer-id]:not(.fulfillment-card--vendor)').forEach((card) => {
        const type = String(card.dataset.customerType || '');
        const id = String(card.dataset.customerId || '');
        if (!type || !id) {
            return;
        }
        bindBgMarkToggle(card, `${type}:${id}`, customerBgMarkKey, customerBgMarks);
    });
})();
