@extends('admin.layout')
@section('title', 'About')
@section('crumb', 'Company Profile / About')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="mb-1">About Page</h2>
        <div class="text-muted">Shown at <a href="{{ route('site.about') }}" target="_blank" rel="noopener">/about</a>.</div>
    </div>
    <a href="{{ route('site.about') }}" target="_blank" rel="noopener" class="btn btn-outline">Preview ↗</a>
</div>

<form method="POST" action="{{ route('admin.company.about.save') }}">
    @csrf
    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body">
                    <div class="form-group">
                        <label>Short description</label>
                        <textarea name="about.description" rows="3" class="form-control">{{ old('about.description', $about['about.description']) }}</textarea>
                        <small class="text-muted">Used on the homepage hero and in the footer.</small>
                    </div>
                    <div class="form-group">
                        <label>Our history</label>
                        <textarea name="about.history" rows="6" class="form-control">{{ old('about.history', $about['about.history']) }}</textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Vision</label>
                                <textarea name="about.vision" rows="5" class="form-control">{{ old('about.vision', $about['about.vision']) }}</textarea>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Mission</label>
                                <textarea name="about.mission" rows="5" class="form-control">{{ old('about.mission', $about['about.mission']) }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header"><h3 class="card-title">Our values</h3></div>
                <div class="card-body">
                    <label class="form-label">One value per line</label>
                    <textarea name="about.values" rows="10" class="form-control font-monospace">{{ old('about.values', is_array($about['about.values']) ? implode("\n", array_map(fn($v) => is_array($v) ? ($v['title'] ?? '') : $v, $about['about.values'])) : '') }}</textarea>
                    <small class="text-muted">Each line becomes one value card on the About page.</small>
                </div>
                <div class="card-footer">
                    <button class="btn btn-primary">Save About page</button>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection
