@extends('site.layout')
@section('title', 'Our team — ' . setting('general.site_name', 'Lindu CMS'))
@section('description', setting('general.team_intro'))

@section('content')
    <x-site.hero
        title="The people behind the work"
        eyebrow="Team"
        :subtitle="setting('general.team_intro', 'The team that makes it happen.')"
        label="Join us"
        url="{{ route('site.careers') }}"
    />

    <x-site.section>
        @if ($members->isEmpty())
            <x-site.empty-state
                title="No team members published yet"
                message="Team members added in the admin appear here."
                icon="ti-users"
            />
        @else
            <div class="row g-4">
                @foreach ($members as $member)
                    <div class="col-6 col-md-4 col-lg-3 text-center">
                        <div class="card h-100">
                            <div class="card-body">
                                @if ($member->photo)
                                    <img src="{{ $member->photo }}" alt="{{ $member->name }}" loading="lazy"
                                         class="rounded-circle mb-3" style="width:7rem;height:7rem;object-fit:cover">
                                @else
                                    <div class="rounded-circle mb-3 d-inline-flex align-items-center justify-content-center"
                                         style="width:7rem;height:7rem;background:var(--tblr-bg-surface-secondary);font-size:2rem;color:var(--tblr-secondary)">
                                        {{ mb_substr($member->name, 0, 1) }}
                                    </div>
                                @endif

                                <h2 class="h5 mb-1">{{ $member->name }}</h2>
                                <p class="text-primary small fw-semibold mb-2">{{ $member->position }}</p>

                                @if ($member->bio)
                                    <p class="text-secondary small mb-0">{{ $member->bio }}</p>
                                @endif

                                @if (filled($member->social))
                                    <div class="d-flex gap-2 justify-content-center mt-3">
                                        @foreach ($member->social as $network => $url)
                                            <a href="{{ $url }}" target="_blank" rel="noopener" class="text-secondary"
                                               aria-label="{{ ucfirst($network) }}">
                                                <i class="ti ti-brand-{{ $network }}"></i>
                                            </a>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </x-site.section>

    <x-site.cta
        title="We are hiring"
        body="If you think you would fit in, we would like to hear from you."
        label="See open roles"
        url="{{ route('site.careers') }}"
    />
@endsection
