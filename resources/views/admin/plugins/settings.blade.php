@extends('admin.layout')
@section('title', 'Plugin Settings')
@section('crumb', 'Plugins / Settings')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="mb-1">Plugin settings</h2>
        <div class="text-muted">Keys prefixed with a plugin slug are written under that group.</div>
    </div>
    <a href="{{ route('admin.plugins.index') }}" class="btn btn-outline">← Plugins</a>
</div>

<form method="POST" action="{{ route('admin.plugins.settings.save') }}">
    @csrf
    <div class="card">
        <div class="card-body">
            @if($plugins->isEmpty())
                <p class="text-muted mb-0">
                    No plugins discovered. Drop a folder containing a <code>plugin.json</code> into <code>plugins/</code>.
                </p>
            @else
                @foreach($plugins as $plugin)
                    <div class="mb-4">
                        <h4>{{ $plugin->name }} <code class="text-muted">{{ $plugin->slug }}</code></h4>
                        @php
                            $prefix = $plugin->slug.'.';
                            $pluginKeys = collect($values->all())->keys()
                                ->filter(function ($k) use ($prefix) { return str_starts_with($k, $prefix); })
                                ->values();
                        @endphp

                        @if($pluginKeys->isEmpty())
                            <p class="text-muted small mb-0">This plugin has not registered any settings yet.</p>
                        @else
                            @foreach($pluginKeys as $key)
                                @php
                                    $row = $values->get($key);
                                    $v = $row['value'] ?? '';
                                    $t = $row['type'] ?? 'text';
                                @endphp
                                <div class="form-group">
                                    <label class="form-label"><code>{{ $key }}</code></label>
                                    @if($t === 'boolean')
                                        <label class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" name="settings[{{ $key }}]" value="1"
                                                   @checked(filter_var($v, FILTER_VALIDATE_BOOLEAN))>
                                            <span class="form-check-label">Enabled</span>
                                        </label>
                                    @elseif($t === 'textarea' || $t === 'text' && str_contains((string) $v, "\n"))
                                        <textarea name="settings[{{ $key }}]" rows="4" class="form-control">{{ $v }}</textarea>
                                    @else
                                        <input class="form-control" name="settings[{{ $key }}]" value="{{ $v }}">
                                    @endif
                                </div>
                            @endforeach
                        @endif
                    </div>
                @endforeach
            @endif
        </div>
        <div class="card-footer text-right">
            <button class="btn btn-primary" @disabled($plugins->isEmpty())>Save plugin settings</button>
        </div>
    </div>
</form>
@endsection
