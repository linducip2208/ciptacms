@props([
    'title' => 'Ready to talk?',
    'body' => null,
    'label' => null,
    'url' => null,
    'secondaryLabel' => null,
    'secondaryUrl' => null,
])

<section class="lindu-section">
    <div class="container-xl">
        <div class="card text-center" style="border:0;background:linear-gradient(135deg,var(--tblr-primary),var(--lindu-secondary))">
            <div class="card-body p-4 p-md-5">
                <h2 class="text-white mb-2">{{ $title }}</h2>

                @if ($body)
                    <p class="mb-4 mx-auto" style="color:rgba(255,255,255,.9);max-width:38rem">{{ $body }}</p>
                @endif

                <div class="d-flex flex-wrap gap-2 justify-content-center">
                    @if ($url)
                        <a href="{{ $url }}" class="btn btn-lg px-4" style="background:#fff;color:var(--tblr-primary)">
                            {{ $label ?: 'Get in touch' }}
                        </a>
                    @endif
                    @if ($secondaryUrl)
                        <a href="{{ $secondaryUrl }}" class="btn btn-lg btn-outline-light px-4">
                            {{ $secondaryLabel }}
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </div>
</section>
