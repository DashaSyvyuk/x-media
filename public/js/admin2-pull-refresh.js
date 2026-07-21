(function () {
    const isTouch = 'ontouchstart' in window || navigator.maxTouchPoints > 0;
    if (!isTouch) {
        return;
    }

    const THRESHOLD = 72;
    const MAX_PULL = 120;

    const indicator = document.createElement('div');
    indicator.className = 'pull-refresh';
    indicator.setAttribute('aria-hidden', 'true');
    indicator.innerHTML = '<div class="pull-refresh__inner"><i class="bi bi-arrow-down"></i></div>';
    document.body.prepend(indicator);

    let startY = 0;
    let armed = false;
    let pulling = false;
    let currentPull = 0;

    function pageScrollTop() {
        return window.scrollY || document.documentElement.scrollTop || 0;
    }

    function sidebarOpen() {
        return document.querySelector('.sidebar.open') !== null;
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
        if (sidebarOpen() || pageScrollTop() > 1 || event.touches.length !== 1) {
            armed = false;
            return;
        }

        startY = event.touches[0].clientY;
        armed = true;
        pulling = false;
    }, { passive: true });

    document.addEventListener('touchmove', (event) => {
        if (!armed || sidebarOpen() || event.touches.length !== 1) {
            return;
        }

        if (pageScrollTop() > 1) {
            armed = false;
            resetIndicator();
            return;
        }

        const dy = event.touches[0].clientY - startY;
        if (dy <= 0) {
            if (pulling) {
                setPull(0);
            }
            return;
        }

        pulling = true;
        event.preventDefault();
        setPull(dy * 0.55);
    }, { passive: false });

    document.addEventListener('touchend', () => {
        if (!armed) {
            return;
        }

        const shouldReload = pulling && currentPull >= THRESHOLD;
        armed = false;
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
        armed = false;
        pulling = false;
        resetIndicator();
    }, { passive: true });
})();
