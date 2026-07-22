document.addEventListener('DOMContentLoaded', () => {
    const linkForm = document.getElementById('fulfillmentLinkForm');
    if (linkForm) {
        document.querySelectorAll('.fulfillment-link-target').forEach((select) => {
            select.addEventListener('change', () => {
                const value = select.value;
                if (!value || !value.includes(':')) {
                    return;
                }

                const [customerType, customerId] = value.split(':');
                document.getElementById('linkVendorId').value = select.dataset.vendorId || '';
                document.getElementById('linkCustomerType').value = customerType;
                document.getElementById('linkCustomerId').value = customerId;
                linkForm.submit();
            });
        });
    }

    document.querySelectorAll('.fulfillment-status-select').forEach((select) => {
        select.addEventListener('change', () => {
            const form = select.closest('form');
            if (form && !select.disabled) {
                form.submit();
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

            form.submit();
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

    const board = document.querySelector('.fulfillment-board');
    if (!board) {
        return;
    }

    let peerMap = {};
    try {
        peerMap = JSON.parse(board.dataset.linkPeers || '{}');
    } catch {
        peerMap = {};
    }

    const cardsByKey = new Map();
    document.querySelectorAll('.fulfillment-card[data-card-key]').forEach((card) => {
        cardsByKey.set(card.dataset.cardKey, card);
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

    document.querySelectorAll('.fulfillment-card[data-card-key]').forEach((card) => {
        card.addEventListener('mouseenter', () => {
            setHover(card.dataset.cardKey || '');
        });

        card.addEventListener('mouseleave', () => {
            window.requestAnimationFrame(() => {
                if (!activePeers || !isOverAnyPeer(activePeers)) {
                    clearHover();
                }
            });
        });
    });

    const bgMarkStorageKey = 'admin2.fulfillment.vendorBgMarks';
    const readBgMarks = () => {
        try {
            const raw = localStorage.getItem(bgMarkStorageKey);
            const parsed = raw ? JSON.parse(raw) : {};

            return parsed && typeof parsed === 'object' ? parsed : {};
        } catch {
            return {};
        }
    };
    const writeBgMarks = (marks) => {
        try {
            localStorage.setItem(bgMarkStorageKey, JSON.stringify(marks));
        } catch {
            // ignore quota / private mode
        }
    };

    let bgMarks = readBgMarks();

    document.querySelectorAll('.fulfillment-card--vendor[data-vendor-id]').forEach((card) => {
        const vendorId = String(card.dataset.vendorId || '');
        const toggleBtn = card.querySelector('[data-bg-mark-toggle]');
        if (!vendorId || !toggleBtn) {
            return;
        }

        const applyMark = (on) => {
            card.classList.toggle('fulfillment-card--bg-mark', on);
            toggleBtn.classList.toggle('is-active', on);
            toggleBtn.setAttribute('aria-pressed', on ? 'true' : 'false');
            toggleBtn.title = on ? 'Повернути звичайний фон' : 'Змінити колір фону';
        };

        applyMark(Boolean(bgMarks[vendorId]));

        toggleBtn.addEventListener('click', (event) => {
            event.preventDefault();
            event.stopPropagation();

            const next = !card.classList.contains('fulfillment-card--bg-mark');
            applyMark(next);

            if (next) {
                bgMarks[vendorId] = 1;
            } else {
                delete bgMarks[vendorId];
            }
            writeBgMarks(bgMarks);
        });
    });
});
