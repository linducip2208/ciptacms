@extends('admin.layout')
@section('title', 'White Label — '.$def['label'])
@section('crumb', 'White Label / '.$def['label'])

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div>
        <h2 class="mb-1">White Label</h2>
        <div class="text-muted">No vendor name is hard-coded anywhere — every string below is read from the database at render time.</div>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ url('/') }}" target="_blank" rel="noopener" class="btn btn-outline">View site ↗</a>
        <a href="{{ url('/login') }}" target="_blank" rel="noopener" class="btn btn-outline">Login screen ↗</a>
    </div>
</div>

<div class="row">
    <div class="col-lg-3">
        <div class="card">
            <div class="list-group list-group-flush">
                @foreach($sections as $slug => $s)
                    <a href="{{ route('admin.whitelabel.section', $slug) }}"
                       class="list-group-item list-group-item-action {{ $slug === $section ? 'active' : '' }}">
                        {{ $s['label'] }}
                    </a>
                @endforeach
                <a href="{{ route('admin.whitelabel.domains.create') }}" class="list-group-item list-group-item-action">
                    Domains
                </a>
            </div>
        </div>
    </div>

    <div class="col-lg-9">
        <form method="POST" action="{{ route('admin.whitelabel.save', $section) }}">
            @csrf
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title mb-0">{{ $def['label'] }}</h3>
                    <div class="text-muted small">{{ $def['description'] }}</div>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        @foreach($def['fields'] as [$key, $label, $type, $help])
                            @php
                                $row = $values->get($key);
                                $val = $row['value'] ?? '';
                                if ($type === 'boolean') $val = $row ? filter_var($row['value'], FILTER_VALIDATE_BOOLEAN) : false;
                            @endphp
                            <div class="col-md-{{ in_array($type, ['textarea', 'code'], true) ? 12 : 6 }}">
                                <label class="form-label">{{ $label }}</label>
                                @if($type === 'boolean')
                                    <label class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="settings[{{ $key }}]" value="1" @checked((bool) $val)>
                                        <span class="form-check-label">Enabled</span>
                                    </label>
                                @elseif($type === 'textarea')
                                    <textarea class="form-control" name="settings[{{ $key }}]" rows="3">{{ $val }}</textarea>
                                @elseif($type === 'code')
                                    <textarea class="form-control font-monospace" name="settings[{{ $key }}]" rows="8">{{ $val }}</textarea>
                                @elseif($type === 'color')
                                    <div class="d-flex gap-2">
                                        <input type="color" value="{{ $val ?: '#000000' }}" class="form-control form-control-color" id="c-{{ $key }}">
                                        <input class="form-control font-monospace" name="settings[{{ $key }}]" id="s-{{ $key }}" value="{{ $val }}">
                                    </div>
                                @elseif($type === 'image')
                                    <div class="d-flex gap-2 align-items-start">
                                        <input class="form-control" name="settings[{{ $key }}]" value="{{ $val }}" placeholder="/storage/… or https://…">
                                        @if($val)<img src="{{ $val }}" alt="" style="width:56px;height:42px;object-fit:cover;border-radius:4px;border:1px solid #e6e9f2">@endif
                                    </div>
                                @else
                                    <input class="form-control" name="settings[{{ $key }}]" value="{{ $val }}">
                                @endif
                                @if($help)<small class="text-muted d-block mt-1">{{ $help }}</small>@endif
                            </div>
                        @endforeach
                    </div>
                </div>
                <div class="card-footer text-right"><button class="btn btn-primary">Save {{ $def['label'] }}</button></div>
            </div>
        </form>

        @if($section === 'branding')
            <div class="card mt-3">
                <div class="card-header"><h3 class="card-title">Domains</h3></div>
                <div class="table-responsive">
                    <table class="table table-vcenter card-table">
                        <thead><tr><th>Domain</th><th>Primary</th><th>Verified</th><th></th></tr></thead>
                        <tbody>
                            @forelse($domains as $d)
                                <tr>
                                    <td><code>{{ $d->domain }}</code></td>
                                    <td>@if($d->is_primary)<span class="badge bg-blue">primary</span>@else — @endif</td>
                                    <td>
                                        <span class="badge bg-{{ $d->is_verified ? 'green' : 'secondary' }}">
                                            {{ $d->is_verified ? 'verified' : 'pending' }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="d-flex gap-2">
                                            @unless($d->is_primary)
                                                <form method="POST" action="{{ route('admin.whitelabel.domains.primary', $d) }}">@csrf
                                                    <button class="text-indigo-600">Make primary</button>
                                                </form>
                                            @endunless
                                            <form method="POST" action="{{ route('admin.whitelabel.domains.destroy', $d) }}"
                                                  onsubmit="return confirm('Remove this domain?')">@csrf @method('DELETE')
                                                <button class="text-rose-600">Remove</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-center text-muted py-4">
                                    No domains configured. <a href="{{ route('admin.whitelabel.domains.create') }}">Add one</a>.
                                </td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
document.querySelectorAll('.form-control-color').forEach(function (picker) {
    var input = document.getElementById(picker.id.replace(/^c-/, 's-'));
    if (!input) return;
    picker.addEventListener('input', function () { input.value = picker.value; });
    input.addEventListener('input', function () {
        if (/^#[0-9a-f]{6}$/i.test(input.value)) picker.value = input.value;
    });
});
</script>
@endpush
