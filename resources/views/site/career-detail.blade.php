@extends('site.layout')
@section('title', ($seo['title'] ?? $item->position) . ' — ' . setting('general.site_name', 'Lindu CMS'))
@section('description', $seo['description'] ?? \Illuminate\Support\Str::limit(strip_tags((string) $item->description), 160))

@section('content')
    <div class="border-bottom">
        <div class="container-xl py-3">
            <x-site.breadcrumbs :items="[
                ['label' => 'Home', 'url' => route('site.home')],
                ['label' => 'Careers', 'url' => route('site.careers')],
                ['label' => $item->position],
            ]" />
        </div>
    </div>

    <x-site.section :tight="true">
        <div class="row g-5">
            <div class="col-lg-7">
                <h1 class="mb-2">{{ $item->position }}</h1>
                <div class="d-flex flex-wrap gap-3 text-secondary small mb-4">
                    @if ($item->location)<span><i class="ti ti-map-pin me-1"></i>{{ $item->location }}</span>@endif
                    @if ($item->employment_type)<span><i class="ti ti-briefcase me-1"></i>{{ $item->employment_type }}</span>@endif
                    @if ($item->deadline)<span><i class="ti ti-clock me-1"></i>Apply before {{ $item->deadline->format('j M Y') }}</span>@endif
                </div>

                @if (! empty($item->description))
                    <div class="lindu-prose">{!! $item->description !!}</div>
                @endif

                @if (filled($item->requirements))
                    <h2 class="h4 mt-4 mb-3">Requirements</h2>
                    <ul class="list-unstyled d-grid gap-2">
                        @foreach ($item->requirements as $requirement)
                            <li class="d-flex gap-2">
                                <i class="ti ti-check-circle text-success mt-1"></i>
                                <span>{{ is_array($requirement) ? ($requirement['title'] ?? '') : $requirement }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>

            <div class="col-lg-5">
                @php $closed = $item->deadline && $item->deadline->isPast(); @endphp

                @if ($closed)
                    <div class="card">
                        <div class="card-body text-center py-4">
                            <i class="ti ti-lock fs-1 text-secondary mb-2"></i>
                            <p class="fw-semibold mb-1">This position has closed</p>
                            <p class="text-secondary small mb-3">Thank you for your interest.</p>
                            <a href="{{ route('site.careers') }}" class="btn btn-outline-primary">See other roles</a>
                        </div>
                    </div>
                @else
                    <div class="card sticky-top" style="lindu-sticky-aside">
                        <div class="card-body">
                            <h2 class="h5 mb-3">Apply for this role</h2>

                            <form method="POST" action="{{ route('site.career.apply', $item) }}" enctype="multipart/form-data">
                                @csrf

                                <div class="mb-3">
                                    <label class="form-label" for="ap-name">Full name *</label>
                                    <input id="ap-name" name="name" value="{{ old('name') }}" class="form-control" required>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label" for="ap-email">Email *</label>
                                    <input id="ap-email" type="email" name="email" value="{{ old('email') }}" class="form-control" required>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label" for="ap-phone">Phone / WhatsApp</label>
                                    <input id="ap-phone" name="phone" value="{{ old('phone') }}" class="form-control">
                                </div>

                                <div class="mb-3">
                                    <label class="form-label" for="ap-cv">CV / Resume</label>
                                    <input id="ap-cv" type="file" name="cv" class="form-control" accept=".pdf,.doc,.docx">
                                    <small class="text-secondary">PDF, DOC or DOCX up to 4MB.</small>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label" for="ap-cover">Cover letter</label>
                                    <textarea id="ap-cover" name="cover_letter" rows="5" class="form-control"
                                              placeholder="Why are you a good fit?">{{ old('cover_letter') }}</textarea>
                                </div>

                                <button class="btn btn-primary w-100">Submit application</button>
                            </form>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </x-site.section>
@endsection
