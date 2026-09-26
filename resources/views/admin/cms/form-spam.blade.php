@extends('admin.layout')
@section('title', 'Spam Protection')
@section('crumb', 'Forms / Spam & Protection')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="mb-1">Spam protection</h2>
        <div class="text-muted">Applies to every builder form. The renderer checks them in this order.</div>
    </div>
    <a href="{{ route('admin.cms.forms.index') }}" class="btn btn-outline">← Forms</a>
</div>

<div class="row g-3">
    <div class="col-lg-7">
        <form method="POST" action="{{ route('admin.cms.form-spam.save') }}">
            @csrf
            <div class="card">
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="honeypot" value="1" @checked($settings->honeypot)>
                                <span class="form-check-label">Honeypot field</span>
                            </label>
                            <small class="text-muted d-block mt-1">
                                A hidden input real users never fill. Anything submitted there is rejected outright.
                            </small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="captcha" value="1" @checked($settings->captcha)>
                                <span class="form-check-label">CAPTCHA (adapter)</span>
                            </label>
                            <small class="text-muted d-block mt-1">
                                Seam only. Wire a provider before enabling, otherwise nothing is challenged.
                            </small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="rate">Rate limit (submissions per minute per IP)</label>
                            <input id="rate" type="number" name="rate_limit_per_minute" value="{{ old('rate_limit_per_minute', $settings->rate_limit_per_minute ?? 5) }}" class="form-control" min="1" max="120">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="fill">Minimum fill time (seconds)</label>
                            <input id="fill" type="number" name="min_fill_seconds" value="{{ old('min_fill_seconds', $settings->min_fill_seconds ?? 2) }}" class="form-control" min="0" max="60">
                            <small class="text-muted">Anything faster than this is treated as a bot.</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="block_disposable_email" value="1" @checked($settings->block_disposable_email)>
                                <span class="form-check-label">Block disposable email domains</span>
                            </label>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label" for="words">Blocked words</label>
                            <textarea id="words" name="blocked_words" rows="5" class="form-control font-monospace">{{ old('blocked_words', is_array($settings->blocked_words) ? implode("\n", $settings->blocked_words) : '') }}</textarea>
                            <small class="text-muted">One per line. Applies in addition to the <a href="{{ route('admin.cms.comments.word-filter') }}">comment word filter</a>.</small>
                        </div>
                    </div>
                </div>
                <div class="card-footer text-right"><button class="btn btn-primary">Save protection settings</button></div>
            </div>
        </form>
    </div>

    <div class="col-lg-5">
        <div class="card h-100">
            <div class="card-header"><h3 class="card-title">How a submission is checked</h3></div>
            <div class="card-body">
                <ol style="padding-left:20px;margin:0;color:#475569">
                    <li style="margin-bottom:10px"><b>Honeypot</b> — hidden field must be empty.</li>
                    <li style="margin-bottom:10px"><b>Fill time</b> — the form carries a timestamp; too fast is a bot.</li>
                    <li style="margin-bottom:10px"><b>Field validation</b> — required, type and length rules.</li>
                    <li style="margin-bottom:10px"><b>Blocked words</b> — matches anywhere in the payload.</li>
                    <li style="margin-bottom:10px"><b>Rate limit</b> — per IP, per form, per minute.</li>
                    <li><b>CAPTCHA</b> — optional adapter, off by default.</li>
                </ol>
                <p class="text-muted small mb-0 mt-3">
                    Every rejection is written to the application log with the reason, so you can tell
                    spam from a genuine mistake.
                </p>
            </div>
        </div>
    </div>
</div>
@endsection
