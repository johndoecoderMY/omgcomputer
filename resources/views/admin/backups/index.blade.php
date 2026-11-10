@extends('layouts.app')

@section('title', 'Backups')

@section('content')
<div class="container-fluid py-3">
    <x-page-header eyebrow="Administration" title="Backups"
        description="Review backup archives stored by Spatie Laravel Backup."
        :back-url="route('dashboard')" back-label="Dashboard">
        <x-slot:actions>
            <form method="POST" action="{{ route('admin.backups.run') }}"
                onsubmit="return confirm('Run a full backup now? This may take several minutes.');">
                @csrf
                <button class="btn btn-primary" type="submit">
                    <i class="bi bi-database me-2" aria-hidden="true"></i>Run backup now
                </button>
            </form>
        </x-slot:actions>
    </x-page-header>

    @if (session('success'))
        <div class="alert alert-success" role="status">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger" role="alert">{{ session('error') }}</div>
    @endif

    <section class="card mb-4" aria-labelledby="selected-table-export-heading">
        <div class="card-body">
            <h2 class="h5" id="selected-table-export-heading">Export selected database tables</h2>
            <p class="text-body-secondary">
                Choose tables to export. The download is a ZIP of CSV data files plus a manifest listing each table's columns.
                Use the data export to reinsert records into a database with a matching schema. For a full pre-migration restore,
                use <strong>Run backup now</strong>.
            </p>
            <form method="POST" action="{{ route('admin.backups.export-tables') }}"
                onsubmit="return confirm('Export the selected table data to a ZIP file?');">
                @csrf
                @error('tables')
                    <div class="alert alert-danger" role="alert">{{ $message }}</div>
                @enderror
                @error('tables.*')
                    <div class="alert alert-danger" role="alert">{{ $message }}</div>
                @enderror
                @if ($databaseTables->isEmpty())
                    <div class="alert alert-warning mb-0" role="alert">No database tables are available to export.</div>
                @else
                    @php($selectedTables = old('tables', []))
                    @php($selectedTables = is_array($selectedTables) ? $selectedTables : [])
                    @php($accountTables = ['users', 'password_reset_tokens', 'sessions', 'roles', 'permissions', 'model_has_roles', 'model_has_permissions', 'role_has_permissions', 'teams', 'team_user', 'departments', 'profile_activities', 'notifications', 'email_server_settings', 'user_email_mailboxes'])
                    <div class="d-flex flex-wrap gap-2 mb-3">
                        <button class="btn btn-sm btn-outline-secondary" type="button" data-select-export-tables="all">
                            Select all tables
                        </button>
                        <button class="btn btn-sm btn-outline-secondary" type="button" data-select-export-tables="accounts">
                            Select account &amp; settings tables
                        </button>
                        <button class="btn btn-sm btn-outline-secondary" type="button" data-select-export-tables="none">
                            Clear selection
                        </button>
                    </div>
                    <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-3 g-2 mb-3">
                        @foreach ($databaseTables as $table)
                            <div class="col">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="tables[]"
                                        id="export-table-{{ $loop->index }}" value="{{ $table }}"
                                        data-account-export-table="{{ in_array($table, $accountTables, true) ? 'true' : 'false' }}"
                                        @checked(in_array($table, $selectedTables, true))>
                                    <label class="form-check-label" for="export-table-{{ $loop->index }}">
                                        {{ $table }}
                                    </label>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <button class="btn btn-outline-primary" type="submit">
                        <i class="bi bi-file-earmark-zip me-2" aria-hidden="true"></i>Export selected tables
                    </button>
                @endif
            </form>
        </div>
    </section>
    @push('scripts')
        <script>
            document.querySelectorAll('[data-select-export-tables]').forEach((button) => {
                button.addEventListener('click', () => {
                    const selection = button.dataset.selectExportTables;
                    document.querySelectorAll('input[name="tables[]"]').forEach((checkbox) => {
                        checkbox.checked = selection === 'all' ||
                            (selection === 'accounts' && checkbox.dataset.accountExportTable === 'true');
                    });
                });
            });
        </script>
    @endpush

    <div class="alert alert-info" role="status">
        <strong>Backup set:</strong> {{ $backupName }}
        <span class="ms-2">Scheduled daily at 2:00 AM when the Laravel scheduler is running.</span>
        <div class="mt-2">Full archives include the configured database and its tables, including user accounts, email settings, roles, permissions, and assignments.</div>
    </div>

    @forelse ($destinations as $destination)
        <div class="card mb-4">            <div class="card-header bg-body d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div>
                    <h2 class="h5 mb-1">Storage: {{ $destination['disk'] }}</h2>
                    <p class="small text-body-secondary mb-0">
                        {{ $destination['backups']->count() }} archive(s)
                        · {{ number_format($destination['total_size'] / 1048576, 2) }} MB total
                    </p>
                </div>
                @if ($destination['reachable'])
                    <span class="badge text-bg-success">Storage available</span>
                @else
                    <span class="badge text-bg-danger">Storage unavailable</span>
                @endif
            </div>

            @if ($destination['newest'])
                <div class="card-body border-bottom">
                    <span class="text-body-secondary">Most recent backup:</span>
                    <strong>{{ $destination['newest']->date()->format('M j, Y g:i A') }}</strong>
                    <span class="badge text-bg-light border ms-1">
                        {{ $destination['newest']->date()->diffForHumans() }}
                    </span>
                    <span class="small text-body-secondary">
                        ({{ number_format($destination['newest']->sizeInBytes() / 1048576, 2) }} MB)
                    </span>
                </div>
            @else
                <div class="card-body border-bottom">
                    <span class="text-body-secondary">Most recent backup:</span>
                    <strong>None found</strong>
                    <span class="small text-body-secondary">Age: unavailable until a backup archive exists.</span>
                </div>
            @endif

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 table-bordered">
                    <thead>
                        <tr><th>Archive</th><th>Created</th><th>Size</th><th>Action</th></tr>
                    </thead>
                    <tbody>
                        @forelse ($destination['backups'] as $backup)
                            <tr>
                                <td class="text-break">{{ basename($backup->path()) }}</td>
                                <td>{{ $backup->date()->format('M j, Y g:i A') }}</td>
                                <td>{{ number_format($backup->sizeInBytes() / 1048576, 2) }} MB</td>
                                <td>
                                    <a class="btn btn-sm btn-outline-primary"
                                        href="{{ route('admin.backups.download', [$destination['disk'], basename($backup->path())]) }}">
                                        <i class="bi bi-download me-1" aria-hidden="true"></i>Download
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-body-secondary py-4">
                                    {{ $destination['reachable'] ? 'No backup archives found on this disk.' : 'Could not connect to this backup disk.' }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @empty
        <div class="alert alert-warning" role="alert">
            No backup storage disks are configured. Set <code>BACKUP_DISK</code> and check the Laravel backup configuration.
        </div>
    @endforelse
</div>
@endsection
