@extends('admin.layout')
@section('title', 'Relations')
@section('crumb', 'Data / Relations')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div>
        <h2 class="mb-1">Relations</h2>
        <div class="text-muted">
            Link records of one content type to another. Resolved on demand via
            <code>?include=</code>, so a plain list costs no extra queries.
        </div>
    </div>
    <select class="form-control" style="max-width:260px" onchange="window.location='{{ route('admin.cms.types.relations') }}?type='+this.value">
        <option value="">Choose a content type…</option>
        @foreach($types as $t)
            <option value="{{ $t->slug }}" @selected(request('type') === $t->slug)>{{ $t->name }}</option>
        @endforeach
    </select>
</div>

@php $current = request('type') ? $types->firstWhere('slug', request('type')) : null; @endphp

@if(!$current)
    <div class="card"><div class="card-body text-center text-muted py-5">
        Pick a content type above to define its relations.
    </div></div>
@else
<div class="row g-3">
    <div class="col-lg-5">
        <div class="card">
            <div class="card-header"><h3 class="card-title mb-0">Add a relation on {{ $current->name }}</h3></div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.cms.types.relations.store', $current) }}">
                    @csrf
                    <div class="form-group">
                        <label>Name *</label>
                        <input name="name" value="{{ old('name') }}" class="form-control font-monospace" required placeholder="author">
                        <small class="text-muted">Lowercase, no spaces. Used as the key in API output.</small>
                    </div>
                    <div class="form-group">
                        <label>Type *</label>
                        <select name="type" class="form-control">
                            @foreach($relationTypes as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        <small class="text-muted">
                            @foreach($pivotTypes as $p){{ $p }} @endforeach
                            are stored in the relations table; the rest live on the record itself.
                        </small>
                    </div>
                    <div class="form-group">
                        <label>Points at *</label>
                        <select name="target" class="form-control" required>
                            @foreach($types as $t)
                                <option value="{{ $t->slug }}" @selected($t->slug === $current->slug)>{{ $t->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Label</label>
                        <input name="label" value="{{ old('label') }}" class="form-control" placeholder="Author">
                    </div>
                    <button class="btn btn-primary">Add relation</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title mb-0">Relations on {{ $current->name }} ({{ count($current->relationDefinitions()) }})</h3>
            </div>
            <div class="table-responsive">
                <table class="table table-vcenter card-table">
                    <thead><tr><th>Name</th><th>Type</th><th>Target</th><th>Label</th><th></th></tr></thead>
                    <tbody>
                        @forelse($current->relationDefinitions() as $r)
                            <tr>
                                <td><code>{{ $r['name'] }}</code></td>
                                <td>
                                    <span class="badge">{{ $r['type'] }}</span>
                                    @if(in_array($r['type'], $pivotTypes, true))
                                        <span class="badge bg-secondary">pivot</span>
                                    @endif
                                </td>
                                <td><code>{{ $r['target'] }}</code></td>
                                <td>{{ $r['label'] }}</td>
                                <td>
                                    <form method="POST" action="{{ route('admin.cms.types.relations.destroy', [$current, $r['name']]) }}"
                                          onsubmit="return confirm('Remove this relation and all its links?')">
                                        @csrf @method('DELETE')
                                        <button class="text-rose-600">Remove</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted py-4">
                                No relations on this content type yet.
                            </td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($current->relationDefinitions())
                <div class="card-footer text-muted small">
                    Read them from the API with
                    <code>GET /api/v1/content-types/{{ $current->slug }}/records?include={{ implode(',', $current->relationNames()) }}</code>
                </div>
            @endif
        </div>

        <div class="card mt-3">
            <div class="card-header"><h3 class="card-title mb-0">All relation types</h3></div>
            <div class="table-responsive">
                <table class="table table-vcenter card-table">
                    <thead><tr><th>Type</th><th>Cardinality</th><th>Storage</th></tr></thead>
                    <tbody>
                        @foreach([
                            'hasOne' => ['One child per parent', 'inline on the child record'],
                            'hasMany' => ['Many children per parent', 'pivot table'],
                            'belongsTo' => ['Each record points at one parent', 'inline on this record'],
                            'belongsToMany' => ['Many-to-many with a pivot', 'pivot table'],
                            'morphOne' => ['One polymorphic child', 'inline on the child record'],
                            'morphMany' => ['Many polymorphic children', 'pivot table'],
                        ] as $key => [$cardinality, $storage])
                            <tr>
                                <td><code>{{ $key }}</code></td>
                                <td class="text-muted small">{{ $cardinality }}</td>
                                <td class="text-muted small">{{ $storage }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endif
@endsection
