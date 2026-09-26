@extends('admin.layout')
@section('title', 'Theme Customizer')
@section('crumb', 'Appearance / Theme Customizer')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div>
        <h2 class="mb-1">Theme Customizer</h2>
        <div class="text-muted">
            Emitted as CSS custom properties, so changes apply without rebuilding any stylesheet.
            @if($active) Active theme: <strong>{{ $active->name }}</strong> v{{ $active->version }}. @endif
        </div>
    </div>
    <a href="{{ url('/') }}" target="_blank" rel="noopener" class="btn btn-outline">View site ↗</a>
</div>

<form method="POST" action="{{ route('admin.themes.customize.save') }}">
    @csrf
    <div class="row g-3">
        @foreach($groups as $slug => $group)
            <div class="col-lg-4">
                <div class="card h-100">
                    <div class="card-header"><h3 class="card-title">{{ $group['label'] }}</h3></div>
                    <div class="card-body">
                        @foreach($group['fields'] as $field)
                            @php
                                [$key, $label, $type, $default] = array_pad($field, 4, null);
                                $options = $field[4] ?? [];
                                $full = $slug.'.'.$key;
                                $val = old('customizer.'.$full, $values[$full] ?? $default);
                            @endphp
                            <div class="form-group">
                                <label class="form-label">{{ $label }}</label>
                                @if($type === 'color')
                                    <div class="d-flex gap-2">
                                        <input type="color" value="{{ $val ?: '#000000' }}" class="form-control form-control-color" id="c-{{ $full }}">
                                        <input class="form-control font-monospace" name="customizer[{{ $full }}]" id="s-{{ $full }}" value="{{ $val }}">
                                    </div>
                                @elseif($type === 'bool')
                                    <label class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="customizer[{{ $full }}]" value="1" @checked((string) $val === '1')>
                                        <span class="form-check-label">Enabled</span>
                                    </label>
                                @elseif($type === 'select')
                                    <select class="form-control" name="customizer[{{ $full }}]">
                                        @foreach($options as $ov => $ol)
                                            <option value="{{ $ov }}" @selected((string) $val === (string) $ov)>{{ $ol }}</option>
                                        @endforeach
                                    </select>
                                @else
                                    <input class="form-control" name="customizer[{{ $full }}]" value="{{ $val }}">
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endforeach
    </div>
    <div class="card mt-3">
        <div class="card-body d-flex justify-content-between align-items-center">
            <span class="text-muted">Values are written to <code>appearance_options</code> under the <code>customizer</code> group.</span>
            <button class="btn btn-primary">Save customizer</button>
        </div>
    </div>
</form>
@endsection
