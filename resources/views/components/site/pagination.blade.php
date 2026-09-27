@props(['paginator' => null, 'label' => true])

@if ($paginator && $paginator->hasPages())
    <nav class="mt-5" aria-label="Pagination">
        @if ($label && method_exists($paginator, 'total'))
            <p class="text-secondary small mb-2">
                Showing {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} of {{ $paginator->total() }}
            </p>
        @endif

        {{-- Tabler's pager markup, driven by the framework paginator. --}}
        <ul class="pagination m-0">
            @if ($paginator->onFirstPage())
                <li class="page-item disabled"><span class="page-link">&laquo;</span></li>
            @else
                <li class="page-item">
                    <a class="page-link" href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Previous">&laquo;</a>
                </li>
            @endif

            @foreach ($paginator->getUrlRange(max(1, $paginator->currentPage() - 2), min($paginator->lastPage(), $paginator->currentPage() + 2)) as $page => $url)
                <li class="page-item {{ $page === $paginator->currentPage() ? 'active' : '' }}">
                    @if ($page === $paginator->currentPage())
                        <span class="page-link">{{ $page }}</span>
                    @else
                        <a class="page-link" href="{{ $url }}">{{ $page }}</a>
                    @endif
                </li>
            @endforeach

            @if ($paginator->hasMorePages())
                <li class="page-item">
                    <a class="page-link" href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Next">&raquo;</a>
                </li>
            @else
                <li class="page-item disabled"><span class="page-link">&raquo;</span></li>
            @endif
        </ul>
    </nav>
@endif
