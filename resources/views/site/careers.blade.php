@extends('site.layout')
@section('title', 'Careers — ' . setting('general.site_name', 'Lindu CMS'))
@section('description', setting('general.careers_intro'))

@section('content')
    <x-site.hero
        title="Join our team"
        eyebrow="Careers"
        :subtitle="setting('general.careers_intro', 'We are always looking for talented people.')"
    />

    <x-site.section>
        <form class="row g-2 justify-content-center mb-4" method="GET" action="{{ route('site.careers') }}">
            @if ($locations->isNotEmpty())
                <div class="col-sm-4">
                    <select name="location" class="form-select" onchange="this.form.submit()" aria-label="Filter by location">
                        <option value="">All locations</option>
                        @foreach ($locations as $location)
                            <option value="{{ $location }}" @selected(request('location') === $location)>{{ $location }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
            <div class="col-sm-4">
                <div class="input-group">
                    <input name="search" value="{{ request('search') }}" class="form-control" placeholder="Search roles…" aria-label="Search roles">
                    <button class="btn btn-primary">Search</button>
                </div>
            </div>
        </form>

        @if ($rows->isEmpty())
            <x-site.empty-state
                title="No open positions right now"
                message="Check back soon, or send us a speculative application."
                icon="ti-briefcase"
            >
                <a href="{{ route('site.contact') }}" class="btn btn-primary mt-3">Send an application</a>
            </x-site.empty-state>
        @else
            <div class="accordion" id="careersAccordion">
                @foreach ($rows as $i => $career)
                    @php $closed = $career->deadline && $career->deadline->isPast(); @endphp
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="ch-{{ $career->id }}">
                            <button class="accordion-button {{ $i > 0 ? 'collapsed' : '' }}" type="button"
                                    data-bs-toggle="collapse" data-bs-target="#cc-{{ $career->id }}"
                                    aria-expanded="{{ $i === 0 ? 'true' : 'false' }}" aria-controls="cc-{{ $career->id }}">
                                <span class="me-2 fw-semibold">{{ $career->position }}</span>
                                <span class="text-secondary small fw-normal">
                                    {{ collect([$career->location, $career->employment_type])->filter()->implode(' · ') }}
                                </span>
                                @if ($closed)
                                    <span class="badge bg-secondary ms-2">Closed</span>
                                @endif
                            </button>
                        </h2>
                        <div id="cc-{{ $career->id }}" class="accordion-collapse collapse {{ $i === 0 ? 'show' : '' }}"
                             aria-labelledby="ch-{{ $career->id }}"
                             @if ($i === 0) data-bs-parent="#careersAccordion" @endif>
                            <div class="accordion-body">
                                @if (! empty($career->description))
                                    <div class="lindu-prose">{!! $career->description !!}</div>
                                @endif

                                @if (filled($career->requirements))
                                    <h3 class="h6 mt-3 mb-2">Requirements</h3>
                                    <ul class="list-unstyled d-grid gap-1">
                                        @foreach ($career->requirements as $requirement)
                                            <li class="d-flex gap-2">
                                                <i class="ti ti-circle-filled" style="font-size:.35rem;margin-top:.55rem;color:var(--tblr-primary)"></i>
                                                <span>{{ is_array($requirement) ? ($requirement['title'] ?? '') : $requirement }}</span>
                                            </li>
                                        @endforeach
                                    </ul>
                                @endif

                                @if ($career->deadline)
                                    <p class="text-secondary small mt-3 mb-0">
                                        <i class="ti ti-clock me-1"></i>
                                        @if ($closed)
                                            Applications closed {{ $career->deadline->format('j M Y') }}
                                        @else
                                            Apply before {{ $career->deadline->format('j M Y') }}
                                        @endif
                                    </p>
                                @endif

                                @unless ($closed)
                                    <a href="{{ route('site.career', $career->slug) }}" class="btn btn-primary mt-3">
                                        Apply for this role
                                    </a>
                                @endunless
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <x-site.pagination :paginator="$rows" />
        @endif
    </x-site.section>
@endsection
