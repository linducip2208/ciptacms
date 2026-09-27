{{--
    Mobile navigation. Same tree, same active-state helper as the desktop
    navbar, so the two can never disagree. Offcanvas gives the nested menu
    room to breathe on a phone.
--}}
<div class="offcanvas offcanvas-start" tabindex="-1" id="linduMobileNav" aria-labelledby="linduMobileNavLabel">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title" id="linduMobileNavLabel">{{ setting('general.site_name', 'Lindu CMS') }}</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>

    <div class="offcanvas-body">
        <ul class="list-unstyled mb-0">
            @foreach ($navMain as $item)
                @php $hasChildren = filled($item['children'] ?? []); @endphp

                <li class="mb-2">
                    @if ($hasChildren)
                        <div class="lindu-micro-label mb-1">{{ $item['title'] }}</div>
                        <ul class="list-unstyled ps-2">
                            @foreach ($item['children'] as $child)
                                @php $childActive = lindu_nav_active($child); @endphp
                                <li>
                                    <a class="d-block py-1 text-decoration-none {{ $childActive ? 'fw-semibold' : '' }}"
                                       href="{{ $child['url'] ?? '#' }}"
                                       @if ($childActive) style="color:var(--tblr-primary)" aria-current="page" @endif
                                       @if (($child['target'] ?? '_self') === '_blank') target="_blank" rel="noopener" @endif>
                                        {{ $child['title'] }}
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        @php $itemActive = lindu_nav_active($item); @endphp
                        <a class="d-block py-1 text-decoration-none {{ $itemActive ? 'fw-semibold' : '' }}"
                           href="{{ $item['url'] ?? '#' }}"
                           @if ($itemActive) style="color:var(--tblr-primary)" aria-current="page" @endif
                           @if (($item['target'] ?? '_self') === '_blank') target="_blank" rel="noopener" @endif>
                            {{ $item['title'] }}
                        </a>
                    @endif
                </li>
            @endforeach
        </ul>

        @if ($navCta)
            <a class="btn btn-primary w-100 mt-4" href="{{ $navCta['url'] ?? '#' }}">{{ $navCta['title'] }}</a>
        @endif
    </div>
</div>
