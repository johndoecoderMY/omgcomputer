   <!-- ===============================================--><!--    JavaScripts--><!-- ===============================================-->
    <script src="{{ asset('vendors/popper/popper.min.js') }}"></script>
    <script src="{{ asset('vendors/bootstrap/bootstrap.min.js') }}"></script>
    <script src="{{ asset('vendors/anchorjs/anchor.min.js') }}"></script>
    <script src="{{ asset('vendors/is/is.min.js') }}"></script>
    <script src="{{ asset('vendors/echarts/echarts.min.js') }}"></script>
    <script src="{{ asset('vendors/prism/prism.js') }}"></script>
    <script src="{{ asset('vendors/lodash/lodash.min.js') }}"></script>
    <script src="{{ asset('vendors/list.js/list.min.js') }}"></script>
    <script src="{{ asset('vendors/jquery/jquery.min.js') }}"></script>
    <script src="{{ asset('vendors/datatables.net/dataTables.min.js') }}"></script>
    <script src="{{ asset('vendors/datatables.net-bs5/dataTables.bootstrap5.min.js') }}"></script>
    <script src="{{ asset('vendors/select2/select2.full.min.js') }}"></script>
    <script src="{{ asset('assets/js/theme.js') }}"></script>
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