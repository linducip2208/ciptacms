@php
    /**
     * Public footer.
     *
     * Columns come from the Menu Engine (the `primary` location, grouped the
     * same way the navbar reads it) plus the company-profile contact
     * settings. Nothing company-specific is hard-coded here.
     */
    $tree = app(\App\Core\Services\MenuService::class)->tree('primary');

    $groups = collect($tree)
        ->filter(fn ($i) => filled($i['children'] ?? []))
        ->take(3);

    $ctaItem = collect($tree)->first(fn ($i) => data_get($i, 'meta.cta'));

    $about = $about ?? [];
    $contact = $contact ?? [];
    $social = $contact['contact.social'] ?? [];

    $year = date('Y');
@endphp

<footer class="footer mt-5 py-5"
        style="background:var(--lindu-footer-background);color:var(--lindu-footer-text)">
    <div class="container-xl">
        <div class="row g-4">
            <div class="col-lg-4">
                <div class="d-flex align-items-center gap-2 mb-3">
                    @if (setting('branding.logo'))
                        <img src="{{ setting('branding.logo') }}" alt="{{ setting('general.site_name') }}"
                             style="height:2rem;width:auto">
                    @endif
                    <span class="fw-bold fs-5" style="color:#fff">{{ setting('general.site_name', 'Lindu CMS') }}</span>
                </div>

                @if (! empty($about['about.description']))
                    <p class="mb-0" style="max-width:24rem">
                        {{ \Illuminate\Support\Str::limit(strip_tags((string) $about['about.description']), 180) }}
                    </p>
                @endif

                @if (filled($social))
                    <div class="d-flex gap-2 mt-3">
                        @foreach ($social as $network => $url)
                            <a href="{{ $url }}" target="_blank" rel="noopener nofollow"
                               class="btn btn-sm btn-outline-light d-inline-flex align-items-center justify-content-center"
                               style="width:2.25rem;height:2.25rem;padding:0"
                               aria-label="{{ ucfirst($network) }}">
                                <i class="ti ti-brand-{{ $network }}"></i>
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>

            @foreach ($groups as $group)
                <div class="col-6 col-lg-2">
                    <h2 class="h6 text-white mb-3">{{ $group['title'] }}</h2>
                    <ul class="list-unstyled d-grid gap-2 mb-0">
                        @foreach ($group['children'] as $child)
                            <li>
                                <a href="{{ $child['url'] ?? '#' }}" class="text-decoration-none"
                                   style="color:var(--lindu-footer-text)">{{ $child['title'] }}</a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endforeach

            <div class="col-6 col-lg-2">
                <h2 class="h6 text-white mb-3">Contact</h2>
                <ul class="list-unstyled d-grid gap-2 mb-0" style="color:var(--lindu-footer-text)">
                    @if ($ctaItem)
                        <li>
                            <a href="{{ $ctaItem['url'] ?? '#' }}" class="text-decoration-none"
                               style="color:var(--lindu-footer-text)">{{ $ctaItem['title'] }}</a>
                        </li>
                    @endif
                    @if (! empty($contact['contact.email']))
                        <li>
                            <a href="mailto:{{ $contact['contact.email'] }}" class="text-decoration-none"
                               style="color:var(--lindu-footer-text)">{{ $contact['contact.email'] }}</a>
                        </li>
                    @endif
                    @if (! empty($contact['contact.phone']))
                        <li>{{ $contact['contact.phone'] }}</li>
                    @endif
                    @if (! empty($contact['contact.address']))
                        <li class="small">{{ $contact['contact.address'] }}</li>
                    @endif
                </ul>
            </div>
        </div>

        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mt-4 pt-3"
             style="border-top:1px solid rgba(255,255,255,.14)">
            <span style="font-size:.875rem">
                &copy; {{ $year }} {{ setting('general.site_name', 'Lindu CMS') }}.
                {{ setting('general.footer', 'All rights reserved.') }}
            </span>

            <ul class="list-unstyled d-flex flex-wrap gap-3 mb-0" style="font-size:.875rem">
                {{-- Legal pages are ordinary CMS pages, so the footer links to
                     them only when an operator has actually published them. --}}
                @foreach (['Privacy' => 'privacy', 'Terms' => 'terms'] as $label => $slug)
                    @php $url = \App\Core\Services\MenuService::pageUrl($slug); @endphp
                    @if ($url)
                        <li>
                            <a href="{{ $url }}" class="text-decoration-none" style="color:var(--lindu-footer-text)">{{ $label }}</a>
                        </li>
                    @endif
                @endforeach

                @if (! setting('branding.hide_powered_by', false) && setting('branding.footer_branding'))
                    <li>{{ setting('branding.footer_branding') }}</li>
                @endif
            </ul>
        </div>
    </div>
</footer>
