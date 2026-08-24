(function () {
    const isTouch = 'ontouchstart' in window || navigator.maxTouchPoints > 0;
    if (!isTouch) {
        return;
    }

    // Pull-to-refresh only starts from the top chrome zone so mid-page
    // selects / forms never compete with preventDefault.
    const PULL_ZONE_PX = 96;
    const ARM_DEAD_ZONE = 24;
    const THRESHOLD = 80;
    const MAX_PULL = 130;

    const indicator = document.createElement('div');
    indicator.className = 'pull-refresh';
    indicator.setAttribute('aria-hidden', 'true');
    indicator.innerHTML = '<div class="pull-refresh__inner"><i class="bi bi-arrow-down"></i></div>';
    document.body.prepend(indicator);

    let startY = 0;
    let armed = false;
    let pulling = false;
    let blocked = false;
    let currentPull = 0;

    function pageScrollTop() {
        return window.scrollY || document.documentElement.scrollTop || 0;
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
            + ' [contenteditable="true"], [role="listbox"], [role="combobox"]',
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

    document.addEventListener('touchstart', (event) => {
        blocked = false;
        armed = false;
        pulling = false;
        resetIndicator();

        if (
            sidebarOpen()
            || modalOpen()
            || pageScrollTop() > 0
            || event.touches.length !== 1
        ) {
            return;
        }

        const touch = event.touches[0];
        if (touch.clientY > PULL_ZONE_PX || isUnsafeTarget(event.target)) {
            return;
        }

        startY = touch.clientY;
        armed = true;
    }, { passive: true, capture: true });

    document.addEventListener('touchmove', (event) => {
        if (blocked || !armed || sidebarOpen() || modalOpen() || event.touches.length !== 1) {
            return;
        }

        if (pageScrollTop() > 0 || isUnsafeTarget(event.target)) {
            disarm();
            return;
        }

        const dy = event.touches[0].clientY - startY;
        if (dy <= ARM_DEAD_ZONE) {
            if (pulling) {
                setPull(0);
            }
            return;
        }

        // Confirmed pull-down from the top zone — block native scroll bounce only now.
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
        disarm();
        blocked = false;
    }, { passive: true, capture: true });

    // If a native picker opens, immediately abort any pending pull.
    document.addEventListener('focusin', (event) => {
        if (event.target instanceof HTMLSelectElement || event.target instanceof HTMLInputElement) {
            disarm();
            blocked = false;
        }
    }, true);
})();
