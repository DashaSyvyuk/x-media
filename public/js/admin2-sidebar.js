(function () {
    function initSidebar() {
        const sidebar = document.getElementById('sidebar');
        const toggle = document.getElementById('sidebarToggle');
        const backdrop = document.getElementById('sidebarBackdrop');

        if (!sidebar || !toggle) {
            return;
        }

        const mobileQuery = window.matchMedia('(max-width: 768px)');

        function isMobile() {
            return mobileQuery.matches;
        }

        function setSidebarOpen(open) {
            sidebar.classList.toggle('open', open);
            document.body.classList.toggle('sidebar-open', open && isMobile());

            if (backdrop) {
                backdrop.classList.toggle('show', open && isMobile());
                backdrop.setAttribute('aria-hidden', open && isMobile() ? 'false' : 'true');
            }
        }

        function closeSidebar() {
            setSidebarOpen(false);
        }

        function toggleSidebar() {
            if (isMobile()) {
                setSidebarOpen(!sidebar.classList.contains('open'));
            } else {
                sidebar.classList.toggle('collapsed');
            }
        }

        toggle.addEventListener('click', toggleSidebar);

        if (backdrop) {
            backdrop.addEventListener('click', closeSidebar);
        }

        sidebar.querySelectorAll('a.sidebar-link, a.sidebar-sublink').forEach((link) => {
            link.addEventListener('click', () => {
                if (isMobile()) {
                    closeSidebar();
                }
            });
        });

        sidebar.querySelectorAll('.sidebar-group-toggle').forEach((button) => {
            button.addEventListener('click', () => {
                button.closest('.sidebar-group')?.classList.toggle('open');
            });
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && sidebar.classList.contains('open')) {
                closeSidebar();
            }
        });

        mobileQuery.addEventListener('change', () => {
            if (!isMobile()) {
                closeSidebar();
            }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initSidebar);
    } else {
        initSidebar();
    }
})();
