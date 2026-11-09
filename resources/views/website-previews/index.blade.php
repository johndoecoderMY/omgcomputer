@extends('layouts.guest')

@section('title', 'Website Previews & Portfolio')

@section('content')
<section class="py-7" style="padding-top: 9rem !important; min-height: 65vh;">
    <div class="container">
        <div class="row justify-content-center text-center mb-5">
            <div class="col-lg-9">
                <span class="text-primary fw-bold text-uppercase fs-10">OMG Computer portfolio</span>
                <h1 class="display-5 fw-bold text-1000 mt-2">Website previews</h1>
                <p class="lead text-700 mb-0">Explore sample website design concepts for different kinds of businesses. These demos use Unsplash stock photography and are not completed client projects. Live preview links appear when available.</p>
            </div>
        </div>

        @if ($previews->isNotEmpty())
            <div class="row g-4">
                @foreach ($previews as $preview)
                    <div class="col-md-6 col-xl-4">
                        <article class="card h-100 border-0 shadow-sm overflow-hidden">
                            <a class="d-block bg-body-tertiary" href="{{ Storage::disk('public')->url($preview->image_path) }}" target="_blank" rel="noopener noreferrer" aria-label="View screenshot for {{ $preview->title }}">
                                <img class="w-100" src="{{ Storage::disk('public')->url($preview->image_path) }}" alt="Website design preview: {{ $preview->title }}" loading="lazy" style="aspect-ratio: 16 / 10; object-fit: cover;">
                            </a>
                            <div class="card-body p-4">
                                @if ($preview->category)<span class="badge text-bg-light border mb-2">{{ $preview->category }}</span>@endif
                                <h2 class="h4 text-1000">{{ $preview->title }}</h2>
                                @if ($preview->description)<p class="text-700 mb-0">{{ $preview->description }}</p>@endif
                            </div>
                            <div class="card-footer bg-body border-0 px-4 pb-4 d-flex flex-wrap gap-2">
                                @if ($preview->preview_url)
                                    <a class="btn btn-primary rounded-pill px-4" href="{{ $preview->preview_url }}" target="_blank" rel="noopener noreferrer">
                                        View live preview <span class="bi bi-box-arrow-up-right ms-1" aria-hidden="true"></span>
                                    </a>
                                @endif
                                <a class="btn btn-outline-primary rounded-pill px-4" href="{{ url('/') }}#contact">Discuss a website</a>
                            </div>
                        </article>
                    </div>
                @endforeach
            </div>
        @else
            <div class="card border-0 shadow-sm text-center p-4 p-md-5">
                <div class="card-body">
                    <span class="bi bi-window-stack display-4 text-primary" aria-hidden="true"></span>
                    <h2 class="h3 text-1000 mt-3">Portfolio coming soon</h2>
                    <p class="text-700">We’re preparing website previews to showcase here. In the meantime, tell us about the website you have in mind.</p>
                    <a class="btn btn-primary rounded-pill px-4" href="{{ url('/') }}#website-pricing">Explore website packages</a>
                </div>
            </div>
        @endif

        <div class="text-center mt-5">
            <p class="text-700">Have a website project in mind?</p>
            <a class="btn btn-success rounded-pill px-4" href="https://wa.me/60104224350?text={{ rawurlencode('Hi OMG Computer, I viewed your website previews and would like to discuss a website project.') }}" target="_blank" rel="noopener noreferrer">
                <span class="bi bi-whatsapp me-2" aria-hidden="true"></span>Discuss your project
            </a>
        </div>
    </div>
</section>
@endsection
