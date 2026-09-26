@extends('admin.layout')
@section('title', 'All Settings')
@section('crumb', 'Settings / All')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="mb-1">All settings ({{ $settings->count() }})</h2>
        <div class="text-muted">Every key stored in the <code>settings</code> table. Values typed as <em>secret</em> are encrypted at rest and never displayed.</div>
    </div>
</div>

<form method="POST" action="{{ route('admin.settings.raw.save') }}">
    @csrf
    <div class="card mb-3">
        <div class="table-responsive">
            <table class="table table-vcenter card-table">
                <thead><tr><th>Group</th><th>Key</th><th>Type</th><th style="width:40%">Value</th></tr></thead>
                <tbody>
                    @forelse($settings as $s)
                        <tr>
                            <td><code>{{ $s->group }}</code></td>
                            <td><code>{{ $s->key }}</code></td>
                            <td>
                                @if($s->type === 'secret')
                                    <span class="badge bg-yellow">encrypted</span>
                                @else
                                    <span class="badge">{{ $s->type }}</span>
                                @endif
                            </td>
                            <td>
                                @if($s->type === 'secret')
                                    <input type="password" class="form-control form-control-sm" disabled placeholder="Stored encrypted">
                                @elseif($s->type === 'boolean')
                                    <select name="settings[{{ $s->key }}]" class="form-control form-control-sm">
                                        <option value="1" @selected($s->value === '1')>Enabled</option>
                                        <option value="0" @selected($s->value !== '1')>Disabled</option>
                                    </select>
                                @else
                                    <input name="settings[{{ $s->key }}]" value="{{ $s->value }}" class="form-control form-control-sm">
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center text-muted py-4">No settings stored yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($settings->isNotEmpty())
            <div class="card-footer text-right"><button class="btn btn-primary">Save all</button></div>
        @endif
    </div>
</form>

<div class="card">
    <div class="card-header"><h3 class="card-title">Add a setting</h3></div>
    <div class="card-body">
        <form method="POST" action="{{ route('admin.settings.raw.save') }}">
            @csrf
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Group</label>
                    <input name="new_group" class="form-control" value="general">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Key</label>
                    <input name="new_key" class="form-control" required placeholder="mygroup.my_key">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Type</label>
                    <select name="new_type" class="form-control">
                        @foreach(['text', 'boolean', 'number', 'json', 'secret', 'image', 'file'] as $t)
                            <option value="{{ $t }}">{{ $t }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Value</label>
                    <input name="new_value" class="form-control">
                </div>
            </div>
            <button class="btn btn-primary mt-3">Add setting</button>
        </form>
    </div>
</div>
@endsection
