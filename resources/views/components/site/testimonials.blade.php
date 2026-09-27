@props(['items' => []])

@if (filled($items))
    <div class="row g-4">
        @foreach ($items as $t)
            <div class="col-md-6 col-lg-4">
                <figure class="card h-100 mb-0">
                    <div class="card-body">
                        <div class="lindu-stars mb-2" aria-label="{{ (int) $t->rating }} out of 5">
                            @for ($i = 0; $i < (int) $t->rating; $i++)&#9733;@endfor
                            @for ($i = (int) $t->rating; $i < 5; $i++)&#9734;@endfor
                        </div>

                        <blockquote class="mb-3 fs-5">“{{ $t->testimonial }}”</blockquote>

                        <figcaption class="d-flex align-items-center gap-2">
                            @if ($t->photo)
                                <img src="{{ $t->photo }}" alt="{{ $t->customer }}" class="rounded-circle" style="width:2.75rem;height:2.75rem;object-fit:cover" loading="lazy">
                            @endif
                            <span>
                                <span class="fw-semibold d-block">{{ $t->customer }}</span>
                                @if ($t->company)
                                    <span class="text-secondary small">{{ $t->company }}</span>
                                @endif
                            </span>
                        </figcaption>
                    </div>
                </figure>
            </div>
        @endforeach
    </div>
@else
    <x-site.empty-state title="No testimonials yet" message="Published testimonials appear here." />
@endif
