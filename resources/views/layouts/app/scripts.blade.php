   <!-- ===============================================--><!--    JavaScripts--><!-- ===============================================-->
    <script src="{{ asset('vendors/popper/popper.min.js') }}"></script>
    <script src="{{ asset('vendors/bootstrap/bootstrap.min.js') }}"></script>
    <script src="{{ asset('vendors/anchorjs/anchor.min.js') }}"></script>
    <script src="{{ asset('vendors/is/is.min.js') }}"></script>
    <script src="{{ asset('vendors/echarts/echarts.min.js') }}"></script>
    <script src="{{ asset('vendors/prism/prism.js') }}"></script>
    <script src="{{ asset('vendors/fontawesome/all.min.js') }}"></script>
    <script src="{{ asset('vendors/lodash/lodash.min.js') }}"></script>
    <script src="{{ asset('vendors/list.js/list.min.js') }}"></script>
    <script src="{{ asset('vendors/jquery/jquery.min.js') }}"></script>
    <script src="{{ asset('vendors/datatables.net/dataTables.min.js') }}"></script>
    <script src="{{ asset('vendors/datatables.net-bs5/dataTables.bootstrap5.min.js') }}"></script>
    <script src="{{ asset('vendors/select2/select2.full.min.js') }}"></script>
    <script src="{{ asset('assets/js/theme.js') }}"></script>
    @vite('resources/js/app.js')
    <script>
        const fuzzyProjectMatcher = (params, data) => {
            const query = $.trim(params.term || '').toLocaleLowerCase();

            if (!query) {
                return data;
            }

            if (data.children) {
                const matchingChildren = data.children
                    .map((child) => fuzzyProjectMatcher(params, child))
                    .filter(Boolean);

                return matchingChildren.length ? { ...data, children: matchingChildren } : null;
            }

            const text = (data.text || '').toLocaleLowerCase();
            const matchesFuzzy = (term) => {
                let position = 0;

                for (const character of term) {
                    position = text.indexOf(character, position);
                    if (position === -1) {
                        return false;
                    }
                    position += 1;
                }

                return true;
            };

            return query.split(/\s+/).every(matchesFuzzy) ? data : null;
        };

        $('select.js-project-search').each(function () {
            const select = $(this);
            const modal = select.closest('.modal');

            select.select2({
                theme: 'bootstrap-5',
                width: '100%',
                matcher: fuzzyProjectMatcher,
                dropdownParent: modal.length ? modal : $(document.body),
            });
        });

        document.querySelectorAll('table[data-datatable]').forEach((table) => {
            const columns = JSON.parse(table.dataset.columns || '[]');
            const filters = JSON.parse(table.dataset.datatableFilters || '[]');
            const $table = window.jQuery(table);
            const dataTable = $table.DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: table.dataset.source,
                    data: (request) => {
                        filters.forEach(({ name, selector }) => {
                            const filter = document.querySelector(selector);
                            if (filter && filter.value) {
                                request[name] = filter.value;
                            }
                        });
                    },
                },
                columns,
                pageLength: 25,
                order: [],
            });

            filters.forEach(({ selector }) => {
                document.querySelector(selector)?.addEventListener('change', () => {
                    dataTable.ajax.reload();
                });
            });
            const search = table.dataset.datatableSearch
                ? document.querySelector(table.dataset.datatableSearch)
                : null;
            if (search) {
                search.addEventListener('input', () => {
                    dataTable.search(search.value).draw();
                });
            }

            let retryScheduled = false;
            $table.on('error.dt', function () {
                if (retryScheduled) {
                    return;
                }

                retryScheduled = true;
                setTimeout(() => {
                    dataTable.ajax.reload(null, false);
                }, 1200);
            });
        });
    </script>