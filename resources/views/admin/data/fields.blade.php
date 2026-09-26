@extends('admin.layout')
@section('title', 'Fields')
@section('crumb', 'Data / Fields')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="mb-1">Field catalogue</h2>
        <div class="text-muted">Every field defined across all content types, and the column each maps to.</div>
    </div>
    <a href="{{ route('admin.cms.types.index') }}" class="btn btn-outline">Content types</a>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-vcenter card-table">
            <thead><tr><th>Content type</th><th>Label</th><th>Column</th><th>Type</th><th>Rules</th><th></th></tr></thead>
            <tbody>
                @php $any = false; @endphp
                @foreach($types as $ct)
                    @foreach($ct->fields ?? [] as $f)
                        @php $any = true; @endphp
                        <tr>
                            <td><b>{{ $ct->name }}</b><div class="text-muted small"><code>{{ $ct->slug }}</code></div></td>
                            <td>{{ $f['name'] ?? $f['label'] }}</td>
                            <td><code>{{ $f['slug'] }}</code></td>
                            <td><span class="badge">{{ $fieldTypes[$f['type']]['label'] ?? $f['type'] }}</span></td>
                            <td class="text-muted small">
                                @if($f['required'] ?? false)<span class="badge bg-red">required</span>@endif
                                @if($f['unique'] ?? false)<span class="badge bg-orange">unique</span>@endif
                                <code class="text-xs">{{ $fieldTypes[$f['type']]['column'] ?? 'text' }}</code>
                            </td>
                            <td><a class="text-indigo-600" href="{{ route('admin.cms.types.edit', $ct) }}">Edit</a></td>
                        </tr>
                    @endforeach
                @endforeach
                @unless($any)
                    <tr><td colspan="6" class="text-center text-muted py-4">No fields defined yet.</td></tr>
                @endunless
            </tbody>
        </table>
    </div>
</div>
@endsection
