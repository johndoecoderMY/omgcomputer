@extends('layouts.app')

@section('title', 'System Maintenance')

@section('content')
<div class="container-fluid py-3">
    <x-page-header eyebrow="Administration" title="System maintenance"
        description="Run database migrations and clear Laravel's generated optimization caches."
        :back-url="route('dashboard')" back-label="Dashboard" />

    @if (session('maintenance_success'))
        <div class="alert alert-success" role="status">{{ session('maintenance_success') }}</div>
    @endif
    @if (session('maintenance_error'))
        <div class="alert alert-danger" role="alert">{{ session('maintenance_error') }}</div>
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
                            <span class="fas fa-code-branch me-2" aria-hidden="true"></span>Git pull
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
                        Apply pending migrations to the configured database. Back up the database before running migrations on a live site.
                    </p>
                    <form method="POST" action="{{ route('admin.maintenance.migrate') }}"
                        onsubmit="return confirm('Run pending database migrations now? Make sure you have a recent database backup.');">
                        @csrf
                        <button class="btn btn-primary" type="submit">
                            <span class="fas fa-database me-2" aria-hidden="true"></span>Run migrations
                        </button>
                    </form>
                </div>
            </section>
        </div>
        <div class="col-12 col-xl-6">
            <section class="card h-100">
                <div class="card-body">
                    <h2 class="h5">Clear optimization caches</h2>
                    <p class="text-body-secondary">
                        Clear cached configuration, routes, events, and compiled views so Laravel can rebuild them from the current application files.
                    </p>
                    <form method="POST" action="{{ route('admin.maintenance.optimize-clear') }}"
                        onsubmit="return confirm('Clear Laravel optimization caches now?');">
                        @csrf
                        <button class="btn btn-outline-primary" type="submit">
                            <span class="fas fa-broom me-2" aria-hidden="true"></span>Optimize clear
                        </button>
                    </form>
                </div>
            </section>
        </div>
    </div>
</div>
@endsection
