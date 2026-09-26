@extends('admin.layout')
@section('title', 'Form Fields')
@section('crumb', 'Forms / Fields')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="mb-1">Form fields</h2>
        <div class="text-muted">Every field across every form. Edit them from the form builder.</div>
    </div>
    <form class="d-flex gap-2" method="GET">
        <select name="form" class="form-control" onchange="this.form.submit()">
            <option value="">All forms</option>
            @foreach($forms as $f)
                <option value="{{ $f->id }}" @selected((int) request('form') === $f->id)>{{ $f->title }}</option>
            @endforeach
        </select>
    </form>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-vcenter card-table">
            <thead><tr><th>Form</th><th>Label</th><th>Name</th><th>Type</th><th>Options</th><th>Rules</th><th>State</th><th></th></tr></thead>
            <tbody>
                @php $rows = $forms->isEmpty() ? $rows : $rows->where('form_id', request('form')); @endphp
                @forelse($rows as $f)
                    <tr>
                        <td>{{ $f->form?->title ?? '—' }}</td>
                        <td><b>{{ $f->label }}</b></td>
                        <td><code>{{ $f->name }}</code></td>
                        <td><span class="badge">{{ $types[$f->type]['label'] ?? $f->type }}</span></td>
                        <td class="text-muted text-xs" style="max-width:180px">
                            <div class="text-truncate-cell">{{ $f->options ? implode(', ', array_slice(array_map('strval', $f->options), 0, 4)) : '—' }}</div>
                        </td>
                        <td>
                            @if($f->is_required)<span class="badge bg-red">required</span>@endif
                            @if($f->is_unique)<span class="badge bg-orange">unique</span>@endif
                        </td>
                        <td>
                            <span class="badge bg-{{ $f->is_active ? 'green' : 'secondary' }}">{{ $f->is_active ? 'active' : 'off' }}</span>
                        </td>
                        <td>
                            <a class="text-indigo-600" href="{{ route('admin.cms.forms.builder', $f->form) }}">Edit in builder</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-muted py-4">No fields yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
