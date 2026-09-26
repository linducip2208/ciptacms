@extends('admin.layout')
@section('title', $def['label'].' Settings')
@section('crumb', 'Settings / '.$def['label'])

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="mb-0">{{ $def['label'] }} settings</h2>
    <a href="{{ route('admin.settings.raw') }}" class="btn btn-outline">Raw key/value editor</a>
</div>

<div class="row">
    <div class="col-lg-3">
        <div class="card">
            <div class="list-group list-group-flush">
                @foreach($tabs as $slug => $t)
                    <a href="{{ route('admin.settings.tab', $slug) }}"
                       class="list-group-item list-group-item-action {{ $slug === $tab ? 'active' : '' }}">
                        {{ $t['label'] }}
                    </a>
                @endforeach
            </div>
        </div>
    </div>

    <div class="col-lg-9">
        <form method="POST" action="{{ route('admin.settings.update') }}">
            @csrf
            <input type="hidden" name="tab" value="{{ $tab }}">

            <div class="card">
                <div class="card-body">
                    <div class="row g-3">
                        @foreach($def['fields'] as $field)
                            @php
                                $key = $field['key'];
                                $type = $field['type'];
                                $row = $values->get($key);
                                $val = $row['value'] ?? ($field['default'] ?? '');
                                if ($type === 'boolean') {
                                    $val = $row ? filter_var($row['value'], FILTER_VALIDATE_BOOLEAN) : (bool) ($field['default'] ?? false);
                                }
                                if ($type === 'secret' && $row) {
                                    $val = '';
                                }
                            @endphp
                            <div class="col-md-{{ in_array($type, ['textarea', 'secret']) ? 12 : 6 }}">
                                <label class="form-label" for="s-{{ $key }}">
                                    {{ $field['label'] }}
                                </label>

                                @if($type === 'boolean')
                                    <label class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="settings[{{ $key }}]" value="1" id="s-{{ $key }}" @checked((bool) $val)>
                                        <span class="form-check-label">Enabled</span>
                                    </label>
                                @elseif($type === 'textarea')
                                    <textarea class="form-control {{ in_array($key, ['seo.custom_head', 'advanced.notes']) ? 'font-monospace' : '' }}"
                                              name="settings[{{ $key }}]" id="s-{{ $key }}" rows="4">{{ $val }}</textarea>
                                @elseif($type === 'secret')
                                    <input type="password" class="form-control" name="settings[{{ $key }}]" id="s-{{ $key }}"
                                           value="" placeholder="{{ $row ? '•••••••• (unchanged)' : 'Not set' }}" autocomplete="new-password">
                                @elseif($type === 'select')
                                    <select class="form-control" name="settings[{{ $key }}]" id="s-{{ $key }}">
                                        @foreach(($field['options'] ?? []) as $ov => $ol)
                                            <option value="{{ $ov }}" @selected((string) $val === (string) $ov)>{{ $ol }}</option>
                                        @endforeach
                                    </select>
                                @elseif($type === 'color')
                                    <div class="d-flex gap-2">
                                        <input type="color" value="{{ $val ?: '#000000' }}" class="form-control form-control-color" id="c-{{ $key }}">
                                        <input class="form-control font-monospace" name="settings[{{ $key }}]" id="s-{{ $key }}" value="{{ $val }}">
                                    </div>
                                @elseif($type === 'image')
                                    <div class="d-flex gap-2 align-items-start">
                                        <input class="form-control" name="settings[{{ $key }}]" id="s-{{ $key }}" value="{{ $val }}" placeholder="/storage/… or https://…">
                                        @if($val)
                                            <img src="{{ $val }}" alt="" style="width:56px;height:42px;object-fit:cover;border-radius:4px;border:1px solid #e6e9f2">
                                        @endif
                                    </div>
                                @elseif($type === 'number')
                                    <input type="number" class="form-control" name="settings[{{ $key }}]" id="s-{{ $key }}" value="{{ $val }}">
                                @else
                                    <input class="form-control" name="settings[{{ $key }}]" id="s-{{ $key }}" value="{{ $val }}">
                                @endif

                                @if(!empty($field['help']))
                                    <small class="text-muted d-block mt-1">{{ $field['help'] }}</small>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
                <div class="card-footer d-flex justify-content-between align-items-center">
                    <span class="text-muted small">Group <code>{{ $def['group'] }}</code> · keys prefixed <code>{{ explode('.', $def['fields'][0]['key'])[0] }}.</code></span>
                    <button class="btn btn-primary">Save {{ $def['label'] }}</button>
                </div>
            </div>
        </form>
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
