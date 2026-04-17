@if ($paginator->hasPages())
<ul class="pagination">
    {{-- Previous --}}
    @if ($paginator->onFirstPage())
        <li class="disabled"><span><i class="bi bi-chevron-left"></i></span></li>
    @else
        <li><a href="{{ $paginator->previousPageUrl() }}" rel="prev"><i class="bi bi-chevron-left"></i></a></li>
    @endif

    @foreach ($elements as $element)
        @if (is_string($element))
            <li class="disabled"><span>{{ $element }}</span></li>
        @endif
        @if (is_array($element))
            @foreach ($element as $page => $url)
                @if ($page == $paginator->currentPage())
                    <li class="active"><span>{{ $page }}</span></li>
                @else
                    <li><a href="{{ $url }}">{{ $page }}</a></li>
                @endif
            @endforeach
        @endif
    @endforeach

    {{-- Next --}}
    @if ($paginator->hasMorePages())
        <li><a href="{{ $paginator->nextPageUrl() }}" rel="next"><i class="bi bi-chevron-right"></i></a></li>
    @else
        <li class="disabled"><span><i class="bi bi-chevron-right"></i></span></li>
    @endif
</ul>
@endif
