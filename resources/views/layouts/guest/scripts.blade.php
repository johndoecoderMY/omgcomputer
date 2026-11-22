   <!-- ===============================================--><!--    JavaScripts--><!-- ===============================================-->
    <script src="{{ asset('vendors/popper/popper.min.js') }}"></script>
    <script src="{{ asset('vendors/bootstrap/bootstrap.min.js') }}"></script>
    <script src="{{ asset('assets/js/theme.js') }}"></script>
    <script>
        (() => {
            const root = document.documentElement;
            const themeButtons = document.querySelectorAll('#guest-settings-menu ~ .dropdown-menu [data-theme-control="theme"]');

            const applyTheme = (theme) => {
                const resolvedTheme = theme === 'dark' ? 'dark' : 'light';

                root.setAttribute('data-bs-theme', resolvedTheme);
                localStorage.setItem('theme', resolvedTheme);

                themeButtons.forEach((button) => {
                    const isSelected = button.value === resolvedTheme;
                    button.classList.toggle('active', isSelected);
                    button.setAttribute('aria-pressed', isSelected ? 'true' : 'false');

                    const checkIcon = button.querySelector('.dropdown-check-icon');
                    if (checkIcon) {
                        checkIcon.classList.toggle('d-none', !isSelected);
                    }
                });
            };

            themeButtons.forEach((button) => {
                button.addEventListener('click', () => applyTheme(button.value));
            });

            applyTheme(localStorage.getItem('theme') || 'light');
        })();

        document.querySelectorAll('#guestNavbar a[href*="#"]').forEach((link) => {
            link.addEventListener('click', () => {
                const target = new URL(link.href, window.location.href);
                const isSamePageSection = target.origin === window.location.origin
                    && target.pathname === window.location.pathname
                    && target.hash;

                if (isSamePageSection) {
                    bootstrap.Collapse.getOrCreateInstance(document.getElementById('guestNavbar'), {
                        toggle: false,
                    }).hide();
                }
            });
        });
    </script>