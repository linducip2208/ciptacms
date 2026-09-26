@extends('admin.layout')
@section('title', 'Homepage')
@section('crumb', 'Appearance / Homepage')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="mb-1">Homepage</h2>
        <div class="text-muted">Choose a Page Builder page for <code>/</code>, or leave it empty to use the built-in sections.</div>
    </div>
    <a href="{{ url('/') }}" target="_blank" rel="noopener" class="btn btn-outline">View homepage ↗</a>
</div>

<form method="POST" action="{{ route('admin.appearance.homepage.save') }}">
    @csrf
    <div class="row g-3">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header"><h3 class="card-title">Page Builder homepage</h3></div>
                <div class="card-body">
                    <div class="form-group">
                        <label>Page rendered at <code>/</code></label>
                        <select name="options[homepage.page_id]" class="form-control">
                            <option value="">— Use the built-in homepage —</option>
                            @foreach($pages as $p)
                                <option value="{{ $p->id }}"
                                        @selected((int) old('options[homepage.page_id]', $options['homepage.page_id'] ?? 0) === $p->id)>
                                    {{ $p->title }} (/p/{{ $p->slug }}){{ $p->status !== 'published' ? ' — '.$p->status : '' }}
                                </option>
                            @endforeach
                        </select>
                        <small class="text-muted">The page must be published for visitors to see it. Only one page can be the homepage at a time.</small>
                    </div>

                    <hr>
                    <h4>Built-in hero</h4>
                    <div class="form-group">
                        <label>Hero title</label>
                        <input name="options[homepage.hero_title]" value="{{ old('options[homepage.hero_title]', $options['homepage.hero_title'] ?? setting('general.tagline', 'Building digital products that grow with you.')) }}" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Hero subtitle</label>
                        <textarea name="options[homepage.hero_subtitle]" rows="2" class="form-control">{{ old('options[homepage.hero_subtitle]', $options['homepage.hero_subtitle'] ?? setting('about.description', '')) }}</textarea>
                    </div>
                    <div class="form-group">
                        <label>Hero background image URL</label>
                        <input name="options[homepage.hero_image]" value="{{ old('options[homepage.hero_image]', $options['homepage.hero_image'] ?? '') }}" class="form-control">
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card">
                <div class="card-header"><h3 class="card-title">Built-in sections</h3></div>
                <div class="card-body">
                    <p class="text-muted small">These only apply when no Page Builder page is selected above.</p>
                    @foreach([
                        'homepage.show_stats' => 'Statistics row',
                        'homepage.show_testimonials' => 'Testimonials',
                        'homepage.show_clients' => 'Client logos',
                        'homepage.show_blog' => 'Latest blog posts',
                    ] as $key => $label)
                        <label class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" name="options[{{ $key }}]" value="1"
                                   @checked(old('options['.$key.']', $options[$key] ?? '1') === '1')>
                            <span class="form-check-label">{{ $label }}</span>
                        </label>
                    @endforeach
                </div>
                <div class="card-footer text-right"><button class="btn btn-primary">Save homepage</button></div>
            </div>
        </div>
    </div>
</form>
@endsection
