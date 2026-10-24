@if ($paginator->hasPages())
    <nav class="d-flex flex-column align-items-center gap-2" aria-label="Product pagination">
        <div class="d-flex flex-wrap justify-content-center align-items-center gap-1">
            @if ($paginator->onFirstPage())
                <span class="btn btn-falcon-default btn-sm disabled" aria-disabled="true">
                    <i class="bi bi-chevron-left me-1" aria-hidden="true"></i>Previous
                </span>
            @else
                <a class="btn btn-falcon-default btn-sm" href="{{ $paginator->previousPageUrl() }}" rel="prev">
                    <i class="bi bi-chevron-left me-1" aria-hidden="true"></i>Previous
                </a>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="btn btn-falcon-default btn-sm disabled" aria-disabled="true">{{ $element }}</span>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page === $paginator->currentPage())
                            <span class="btn btn-falcon-default btn-sm text-primary" aria-current="page">{{ $page }}</span>
                        @else
                            <a class="btn btn-falcon-default btn-sm" href="{{ $url }}" aria-label="Go to page {{ $page }}">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <a class="btn btn-falcon-default btn-sm" href="{{ $paginator->nextPageUrl() }}" rel="next">
                    Next<i class="bi bi-chevron-right ms-1" aria-hidden="true"></i>
                </a>
            @else
                <span class="btn btn-falcon-default btn-sm disabled" aria-disabled="true">
                    Next<i class="bi bi-chevron-right ms-1" aria-hidden="true"></i>
                </span>
            @endif
        </div>

        <p class="small text-body-secondary mb-0">Showing {{ $paginator->firstItem() ?? 0 }} to {{ $paginator->lastItem() ?? 0 }} of {{ number_format($paginator->total()) }} results</p>
    </nav>
@endif
