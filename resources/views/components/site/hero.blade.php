@props([
    'title' => null,
    'eyebrow' => null,
    'subtitle' => null,
    'image' => null,
    'ctaLabel' => null,
    'ctaUrl' => null,
    'align' => 'center', // center | left
    'background' => null,
])

<section
    @if ($background) style="background-image:linear-gradient(180deg,{{ $background }},{{ $background }}dd);background-size:cover;background-position:center"
    @else style="background:linear-gradient(135deg,var(--tblr-primary),var(--lindu-secondary))" @endif
>
    <div class="py-5" style="padding-block: clamp(3.5rem, 9vw, 6.5rem)">
        <div class="container-xl">
            <div class="row justify-content-center">
                <div class="col-lg-9 text-center">
                    @if ($eyebrow)
                        <span class="lindu-eyebrow" style="color:rgba(255,255,255,.85)">{{ $eyebrow }}</span>
                    @endif

                    <h1 class="display-4 fw-bold mb-3" style="color:#fff">{{ $title }}</h1>

                    @if ($subtitle)
                        <p class="lead mb-0 mx-auto" style="color:rgba(255,255,255,.9);max-width:42rem">{{ $subtitle }}</p>
                    @endif

                    @if ($ctaUrl)
                        <div class="mt-4">
                            <a href="{{ $ctaUrl }}" class="btn btn-lg px-4" style="background:#fff;color:var(--tblr-primary)">
                                {{ $ctaLabel ?: 'Get in touch' }}
                            </a>
                        </div>
                    @endif

                    @if (trim($slot))
                        <div class="mt-4">{{ $slot }}</div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</section>
