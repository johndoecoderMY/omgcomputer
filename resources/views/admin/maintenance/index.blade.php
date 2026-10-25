@extends('layouts.app')

@section('title', 'System Maintenance')

@section('content')
<div class="container-fluid py-3">
    <x-page-header eyebrow="Administration" title="System maintenance"
        description="Manage application maintenance tasks, database migrations, and generated storage links."
        :back-url="route('dashboard')" back-label="Dashboard" />

    @if (session('maintenance_success'))
        <div class="alert alert-success" role="status">{{ session('maintenance_success') }}</div>
    @endif
    @if (session('maintenance_error'))
        <div class="alert alert-danger" role="alert">{{ session('maintenance_error') }}</div>
    @endif
    @if ($errors->has('confirmation'))
        <div class="alert alert-danger" role="alert">{{ $errors->first('confirmation') }}</div>
    @endif
    @if (filled(session('maintenance_output')))
        <section class="card mb-3" aria-labelledby="maintenance-output-heading">
            <div class="card-header bg-body">
                <h2 class="h6 mb-0" id="maintenance-output-heading">Command output</h2>
            </div>
            <pre class="card-body mb-0 text-wrap"><code>{{ session('maintenance_output') }}</code></pre>
        </section>
    @endif

    <div class="row g-3">
        <div class="col-12">
            <section class="card border-warning">
                <div class="card-body">
                    <h2 class="h5">Update from Git</h2>
                    <p class="text-body-secondary">
                        Pull the configured branch into the application directory using a fast-forward-only update. The working tree must have no tracked local changes.
                    </p>
                    <form method="POST" action="{{ route('admin.maintenance.git-pull') }}"
                        onsubmit="return confirm('Pull the latest changes into the live application now?');">
                        @csrf
                        <button class="btn btn-warning" type="submit">
                            <span class="bi bi-diagram-2 me-2" aria-hidden="true"></span>Git pull
                        </button>
                    </form>
                </div>
            </section>
        </div>
        <div class="col-12 col-xl-6">
            <section class="card h-100">
                <div class="card-body">
                    <h2 class="h5">Build frontend assets</h2>
                    <p class="text-body-secondary">
                        Run <code>npm run build</code> to generate the production Vite assets in <code>public/build</code>.
                        Node.js, npm, and the project dependencies must be installed on the server.
                    </p>
                    <form method="POST" action="{{ route('admin.maintenance.build-assets') }}"
                        onsubmit="return confirm('Build production frontend assets on the server now?');">
                        @csrf
                        <button class="btn btn-outline-primary" type="submit">
                            <span class="bi bi-box me-2" aria-hidden="true"></span>Build assets
                        </button>
                    </form>
                </div>
            </section>
        </div>
        <div class="col-12 col-xl-6">
            <section class="card h-100">
                <div class="card-body">
                    <h2 class="h5">Database migrations</h2>
                    <p class="text-body-secondary">
                        Apply pending migrations to the configured database without changing existing tables or data. Back up the database before running migrations on a live site.
                    </p>
                    <form method="POST" action="{{ route('admin.maintenance.migrate') }}"
                        onsubmit="return confirm('Run pending database migrations now? Make sure you have a recent database backup.');">
                        @csrf
                        <button class="btn btn-primary" type="submit">
                            <span class="bi bi-database me-2" aria-hidden="true"></span>Run pending migrations
                        </button>
                    </form>
                    <hr>
                    <h3 class="h6 text-danger">Fresh database and seed</h3>
                    <p class="small text-danger">
                        Destructive: drop every database table and all existing data, recreate the schema, then run the database seeders. Only use this when you intentionally want to reset this database. Back up anything you need first.
                    </p>
                    <form method="POST" action="{{ route('admin.maintenance.migrate.fresh-seed') }}"
                        onsubmit="return confirm('WARNING: This permanently deletes all database tables and data, then recreates the schema and runs seeders. Continue only if you have a backup and intend to reset this database.');">
                        @csrf
                        <label class="form-label small fw-semibold" for="fresh-seed-confirmation">Type RESET AND SEED DATABASE to confirm</label>
                        <input class="form-control mb-2" id="fresh-seed-confirmation" name="confirmation"
                            autocomplete="off" required pattern="RESET AND SEED DATABASE"
                            value="{{ old('confirmation') }}">
                        <button class="btn btn-danger" type="submit">
                            <span class="bi bi-exclamation-triangle me-2" aria-hidden="true"></span>Fresh migrate &amp; seed
                        </button>
                    </form>
                </div>
            </section>
        </div>
        <div class="col-12 col-xl-6">
            <section class="card h-100">
                <div class="card-body">
                    <h2 class="h5">Public storage link</h2>
                    <p class="text-body-secondary">
                        Create the <code>public/storage</code> link to <code>storage/app/public</code> so uploaded profile photos and other public files can be served by the website.
                    </p>
                    <form method="POST" action="{{ route('admin.maintenance.storage-link') }}"
                        onsubmit="return confirm('Create the public storage link for uploaded files?');">
                        @csrf
                        <button class="btn btn-outline-primary" type="submit">
                            <span class="bi bi-link-45deg me-2" aria-hidden="true"></span>Create storage link
                        </button>
                    </form>
                    <hr>
                    <h2 class="h5">Clear optimization caches</h2>
                    <p class="text-body-secondary">
                        Clear cached configuration, routes, events, and compiled views so Laravel can rebuild them from the current application files.
                    </p>
                    <form method="POST" action="{{ route('admin.maintenance.optimize-clear') }}"
                        onsubmit="return confirm('Clear Laravel optimization caches now?');">
                        @csrf
                        <button class="btn btn-outline-primary" type="submit">
                            <span class="bi bi-brush me-2" aria-hidden="true"></span>Optimize clear
                        </button>
                    </form>
                </div>
            </section>
        </div>
    </div>
</div>
@endsection
