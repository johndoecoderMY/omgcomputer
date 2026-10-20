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

        document.querySelectorAll('table[data-datatable]').forEach((table) => {
            const columns = JSON.parse(table.dataset.columns || '[]');
            const filters = JSON.parse(table.dataset.datatableFilters || '[]');
            const $table = window.jQuery(table);
            const tableContainer = table.closest('.table-responsive') || table;
            const retryNotice = document.createElement('div');
            retryNotice.className = 'alert alert-warning d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2';
            retryNotice.setAttribute('role', 'alert');
            retryNotice.hidden = true;

            const retryMessage = document.createElement('span');
            retryMessage.textContent = 'Could not load this table.';
            const retryButton = document.createElement('button');
            retryButton.className = 'btn btn-sm btn-warning';
            retryButton.type = 'button';
            retryButton.textContent = 'Retry';
            retryNotice.append(retryMessage, retryButton);
            tableContainer.parentNode.insertBefore(retryNotice, tableContainer);

            let dataTable;
            let retryCount = 0;
            let retryTimer = null;

            const clearRetry = () => {
                if (retryTimer !== null) {
                    window.clearTimeout(retryTimer);
                    retryTimer = null;
                }
                retryCount = 0;
                retryNotice.hidden = true;
                retryButton.disabled = false;
                retryButton.textContent = 'Retry';
            };

            const handleLoadFailure = () => {
                retryNotice.hidden = false;
                retryButton.disabled = false;
                retryButton.textContent = 'Retry';

                if (retryTimer !== null || retryCount >= 2) {
                    return;
                }

                retryCount += 1;
                retryButton.disabled = true;
                retryButton.textContent = 'Retrying...';
                retryTimer = window.setTimeout(() => {
                    retryTimer = null;
                    dataTable.ajax.reload(null, false);
                }, 1000 * retryCount);
            };

            $table.on('xhr.dt', (_event, _settings, json) => {
                if (json !== null) {
                    clearRetry();
                } else {
                    handleLoadFailure();
                }
            });
            $table.on('error.dt', handleLoadFailure);

            dataTable = $table.DataTable({
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

            retryButton.addEventListener('click', () => {
                if (retryTimer !== null) {
                    window.clearTimeout(retryTimer);
                    retryTimer = null;
                }
                retryCount = 0;
                retryButton.disabled = true;
                retryButton.textContent = 'Loading...';
                dataTable.ajax.reload(null, false);
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