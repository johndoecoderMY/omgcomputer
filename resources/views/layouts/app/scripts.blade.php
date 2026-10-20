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
        const fuzzySelectMatcher = (params, data) => {
            const query = $.trim(params.term || '').toLocaleLowerCase();

            if (!query) {
                return data;
            }

            if (data.children) {
                const matchingChildren = data.children
                    .map((child) => fuzzySelectMatcher(params, child))
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

        $('select.js-project-search, select.js-user-search').each(function () {
            const select = $(this);
            const modal = select.closest('.modal');

            select.select2({
                theme: 'bootstrap-5',
                width: '100%',
                placeholder: select.data('placeholder'),
                allowClear: !select.prop('multiple'),
                matcher: fuzzySelectMatcher,
                dropdownParent: modal.length ? modal : $(document.body),
            });
        });

        window.jQuery.fn.dataTable.ext.errMode = 'none';

        const dataTableRetryStates = new WeakMap();
        const retryDataTable = (settings) => {
            const state = dataTableRetryStates.get(settings.nTable);
            if (!state) {
                return;
            }

            if (state.timer !== null) {
                return;
            }

            if (state.retryCount >= 2) {
                state.button.classList.remove('d-none');
                state.button.disabled = false;
                state.button.textContent = 'Retry';
                return;
            }

            state.retryCount += 1;
            state.button.disabled = true;
            state.button.textContent = 'Loading...';
            state.timer = window.setTimeout(() => {
                state.timer = null;
                settings.oInstance.api().ajax.reload(null, false);
            }, 1000 * state.retryCount);
        };

        window.jQuery(document)
            .on('preInit.dt', (_event, settings) => {
                const table = settings.nTable;
                const tableContainer = table.closest('.table-responsive') || table;
                const button = document.createElement('button');
                button.className = 'btn btn-sm btn-outline-warning mb-2 d-none';
                button.type = 'button';
                button.textContent = 'Retry';
                button.setAttribute('aria-label', 'Retry loading table');
                tableContainer.parentNode.insertBefore(button, tableContainer);

                const state = { button, retryCount: 0, timer: null };
                dataTableRetryStates.set(table, state);

                button.addEventListener('click', () => {
                    if (state.timer !== null) {
                        window.clearTimeout(state.timer);
                        state.timer = null;
                    }
                    state.retryCount = 0;
                    button.classList.add('d-none');
                    button.disabled = true;
                    button.textContent = 'Loading...';
                    settings.oInstance.api().ajax.reload(null, false);
                });
            })
            .on('xhr.dt', (_event, settings, json) => {
                const state = dataTableRetryStates.get(settings.nTable);
                if (!state) {
                    return;
                }

                if (json === null) {
                    retryDataTable(settings);
                    return;
                }

                if (state.timer !== null) {
                    window.clearTimeout(state.timer);
                    state.timer = null;
                }
                state.retryCount = 0;
                state.button.classList.add('d-none');
                state.button.disabled = false;
                state.button.textContent = 'Retry';
            })
            .on('error.dt', (_event, settings) => retryDataTable(settings));

        document.querySelectorAll('table[data-datatable]').forEach((table) => {
            const columns = JSON.parse(table.dataset.columns || '[]');
            const filters = JSON.parse(table.dataset.datatableFilters || '[]');
            const $table = window.jQuery(table);
            const dataTable = window.jQuery.fn.DataTable.isDataTable(table)
                ? $table.DataTable()
                : $table.DataTable({
                    processing: true,
                    serverSide: true,
                    ajax: {
                        url: table.dataset.source,
                        timeout: 30000,
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
        });
    </script>