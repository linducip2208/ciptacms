@extends('admin.layout')
@section('title', 'Footer')
@section('crumb', 'Appearance / Footer')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="mb-0">Footer</h2>
    <a href="{{ url('/') }}" target="_blank" rel="noopener" class="btn btn-outline">View site ↗</a>
</div>

<form method="POST" action="{{ route('admin.appearance.footer.save') }}">
    @csrf
    <div class="card">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Copyright line</label>
                    <input name="options[footer.copyright]" value="{{ old('options[footer.copyright]', $options['footer.copyright'] ?? '© '.date('Y').' '.setting('general.site_name', 'Lindu CMS')) }}" class="form-control">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Footer about text</label>
                    <textarea name="options[footer.about]" rows="2" class="form-control">{{ old('options[footer.about]', $options['footer.about'] ?? setting('about.description', '')) }}</textarea>
                </div>
                <div class="col-md-4">
                    <label class="form-check form-switch mt-4">
                        <input class="form-check-input" type="checkbox" name="options[footer.show_social]" value="1"
                               @checked(old('options[footer.show_social]', $options['footer.show_social'] ?? '1') === '1')>
                        <span class="form-check-label">Show social links</span>
                    </label>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Social links override</label>
                    <input name="options[footer.social_links]" value="{{ old('options[footer.social_links]', $options['footer.social_links'] ?? '') }}" class="form-control" placeholder="Falls back to Company Profile → Contact">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Vendor branding</label>
                    <input name="options[footer.branding]" value="{{ old('options[footer.branding]', $options['footer.branding'] ?? setting('branding.footer_branding', '')) }}" class="form-control" placeholder="Leave empty to hide">
                </div>
            </div>
        </div>
        <div class="card-footer text-right"><button class="btn btn-primary">Save footer</button></div>
    </div>
</form>
@endsection
