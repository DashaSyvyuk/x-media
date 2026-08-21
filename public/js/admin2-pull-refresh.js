(function () {
    const isTouch = 'ontouchstart' in window || navigator.maxTouchPoints > 0;
    if (!isTouch) {
        return;
    }

    const THRESHOLD = 72;
    const MAX_PULL  = 120;

    const indicator = document.createElement('div');
    indicator.className = 'pull-refresh';
    indicator.setAttribute('aria-hidden', 'true');
    indicator.innerHTML = '<div class="pull-refresh__inner"><i class="bi bi-arrow-down"></i></div>';
    document.body.prepend(indicator);

    let startY     = 0;
    let armed      = false;
    let pulling    = false;
    let currentPull = 0;
    // Track whether the initial touch was on or inside a <select>.
    // If so, we must NEVER call preventDefault — iOS kills the picker.
    let touchOnSelect = false;

    function pageScrollTop() {
        return window.scrollY || document.documentElement.scrollTop || 0;
    }

    function sidebarOpen() {
        return document.querySelector('.sidebar.open') !== null;
    }

    /**
     * Returns true when the touch started on any element that has its own
     * native gesture — select, input, textarea, contenteditable, scrollable
     * containers, etc.  We arm pull-to-refresh only when this is false.
     */
    function isInteractiveOrScrollable(target) {
        if (!(target instanceof Element)) {
            return false;
        }

        // Explicit interactive elements — never preventDefault near these.
        if (target.closest('select, input, textarea, [contenteditable="true"]')) {
            return true;
        }

        // Scrollable containers (overflow scroll/auto) that are not the root.
        let el = target;
        while (el && el !== document.body) {
            const style = window.getComputedStyle(el);
            const oy = style.overflowY;
            if ((oy === 'scroll' || oy === 'auto') && el.scrollHeight > el.clientHeight + 2) {
                return true;
            }
            el = el.parentElement;
        }

        return false;
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

    document.addEventListener('touchstart', (event) => {
        touchOnSelect = false;

        if (
            sidebarOpen()
            || pageScrollTop() > 1
            || event.touches.length !== 1
        ) {
            armed = false;
            return;
        }

        const target = event.target;
        if (isInteractiveOrScrollable(target)) {
            touchOnSelect = true;
            armed = false;
            return;
        }

        startY  = event.touches[0].clientY;
        armed   = true;
        pulling = false;
    }, { passive: true });

    document.addEventListener('touchmove', (event) => {
        // CRITICAL: never interfere when a select/input was touched.
        if (touchOnSelect) {
            return;
        }

        if (!armed || sidebarOpen() || event.touches.length !== 1) {
            return;
        }

        // Re-check mid-gesture: user may have scrolled into an element.
        if (pageScrollTop() > 1) {
            armed = false;
            resetIndicator();
            return;
        }

        const dy = event.touches[0].clientY - startY;

        // Small movement — don't block, might still be a tap.
        if (dy <= 8) {
            if (pulling) {
                setPull(0);
            }
            return;
        }

        // Only call preventDefault when we are clearly pulling down and we
        // know the touch did NOT start on an interactive element.
        pulling = true;
        event.preventDefault();
        setPull(dy * 0.55);
    }, { passive: false });

    document.addEventListener('touchend', () => {
        touchOnSelect = false;

        if (!armed) {
            return;
        }

        const shouldReload = pulling && currentPull >= THRESHOLD;
        armed   = false;
        pulling = false;

        if (shouldReload) {
            indicator.classList.add('pull-refresh--loading', 'pull-refresh--visible');
            indicator.style.setProperty('--pull', Math.min(THRESHOLD, MAX_PULL) + 'px');
            window.location.reload();
            return;
        }

        resetIndicator();
    }, { passive: true });

    document.addEventListener('touchcancel', () => {
        touchOnSelect = false;
        armed   = false;
        pulling = false;
        resetIndicator();
    }, { passive: true });
})();
