(function () {
    const isTouch = 'ontouchstart' in window || navigator.maxTouchPoints > 0;
    if (!isTouch) {
        return;
    }

    // Arm only when already at the top of the page. Form controls never start a pull.
    const ARM_DEAD_ZONE = 36;
    const THRESHOLD = 84;
    const MAX_PULL = 140;

    const indicator = document.createElement('div');
    indicator.className = 'pull-refresh';
    indicator.setAttribute('aria-hidden', 'true');
    indicator.innerHTML = '<div class="pull-refresh__inner"><i class="bi bi-arrow-down"></i></div>';
    document.body.prepend(indicator);

    let startY = 0;
    let startX = 0;
    let armed = false;
    let pulling = false;
    let blocked = false;
    let currentPull = 0;

    function pageScrollTop() {
        return window.scrollY
            || document.documentElement.scrollTop
            || document.body.scrollTop
            || 0;
    }

    function sidebarOpen() {
        return document.body.classList.contains('sidebar-open')
            || document.querySelector('.sidebar.open') !== null;
    }

    function modalOpen() {
        return document.body.classList.contains('modal-open')
            || document.querySelector('.modal.show') !== null;
    }

    function isUnsafeTarget(target) {
        if (!(target instanceof Element)) {
            return true;
        }

        return Boolean(target.closest(
            'select, option, input, textarea, button, a, label, summary,'
            + ' .form-select, .form-control, .dropdown-menu, .modal,'
            + ' .sidebar, .sidebar-backdrop, [contenteditable="true"],'
            + ' [role="listbox"], [role="combobox"], [data-bs-toggle]',
        ));
    }

    function resetIndicator() {
        indicator.classList.remove('pull-refresh--ready', 'pull-refresh--loading', 'pull-refresh--visible');
        indicator.style.setProperty('--pull', '0px');
        currentPull = 0;
    }

    function setPull(distance) {
        currentPull = Math.min(MAX_PULL, Math.max(0, distance));
        indicator.style.setProperty('--pull', currentPull + 'px');
        indicator.classList.toggle('pull-refresh--visible', currentPull > 8);
        indicator.classList.toggle('pull-refresh--ready', currentPull >= THRESHOLD);
    }

    function disarm() {
        armed = false;
        pulling = false;
        blocked = true;
        resetIndicator();
    }

    function abortPull() {
        armed = false;
        pulling = false;
        blocked = false;
        resetIndicator();
    }

    // Allow ui-recover / selects to cancel a stuck pull gesture.
    window.__admin2AbortPullRefresh = abortPull;

    document.addEventListener('touchstart', (event) => {
        blocked = false;
        armed = false;
        pulling = false;
        resetIndicator();

        if (
            sidebarOpen()
            || modalOpen()
            || pageScrollTop() > 2
            || event.touches.length !== 1
            || isUnsafeTarget(event.target)
        ) {
            return;
        }

        const touch = event.touches[0];
        startY = touch.clientY;
        startX = touch.clientX;
        armed = true;
    }, { passive: true, capture: true });

    document.addEventListener('touchmove', (event) => {
        if (blocked || !armed || sidebarOpen() || modalOpen() || event.touches.length !== 1) {
            return;
        }

        if (pageScrollTop() > 2 || isUnsafeTarget(event.target)) {
            disarm();
            return;
        }

        const touch = event.touches[0];
        const dy = touch.clientY - startY;
        const dx = Math.abs(touch.clientX - startX);

        // Horizontal swipe / page pan — never hijack.
        if (dx > dy && dx > 18) {
            disarm();
            return;
        }

        if (dy <= ARM_DEAD_ZONE) {
            if (pulling) {
                setPull(0);
            }
            return;
        }

        // Confirmed vertical pull from top — block bounce only after dead zone.
        pulling = true;
        event.preventDefault();
        setPull((dy - ARM_DEAD_ZONE) * 0.55);
    }, { passive: false, capture: true });

    document.addEventListener('touchend', () => {
        if (!armed) {
            blocked = false;
            return;
        }

        const shouldReload = pulling && currentPull >= THRESHOLD;
        armed = false;
        pulling = false;
        blocked = false;

        if (shouldReload) {
            indicator.classList.add('pull-refresh--loading', 'pull-refresh--visible');
            indicator.style.setProperty('--pull', Math.min(THRESHOLD, MAX_PULL) + 'px');
            window.location.reload();
            return;
        }

        resetIndicator();
    }, { passive: true, capture: true });

    document.addEventListener('touchcancel', () => {
        abortPull();
    }, { passive: true, capture: true });

    document.addEventListener('focusin', (event) => {
        if (
            event.target instanceof HTMLSelectElement
            || event.target instanceof HTMLInputElement
            || event.target instanceof HTMLTextAreaElement
        ) {
            abortPull();
        }
    }, true);
})();
