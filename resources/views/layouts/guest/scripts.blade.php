   <!-- ===============================================--><!--    JavaScripts--><!-- ===============================================-->
    <script src="{{ asset('vendors/popper/popper.min.js') }}"></script>
    <script src="{{ asset('vendors/bootstrap/bootstrap.min.js') }}"></script>
    <script>
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