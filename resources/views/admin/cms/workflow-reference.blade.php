@extends('admin.layout')
@section('title', $title)
@section('crumb', 'Workflow / '.$title)

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="mb-1">{{ $title }}</h2>
        <div class="text-muted">{{ $description }}</div>
    </div>
    <a href="{{ $back }}" class="btn btn-outline">← Workflows</a>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-vcenter card-table">
            <thead><tr><th>Key</th><th>Description</th><th>In use</th></tr></thead>
            <tbody>
                @forelse($items as $item)
                    <tr>
                        <td><code>{{ $item['key'] }}</code></td>
                        <td>{{ $item['label'] }}</td>
                        <td class="text-muted small">
                            @php
                                $inUse = \App\Models\Workflow::query()
                                    ->where($title === 'Triggers' ? 'trigger_event' : 'name', '!=', '')
                                    ->get()
                                    ->filter(function ($w) use ($item, $title) {
                                        $haystack = json_encode($title === 'Triggers' ? $w->trigger_event : $w->{strtolower($title) === 'actions' ? 'actions' : 'conditions'});
                                        return str_contains((string) $haystack, $item['key']);
                                    })->count();
                            @endphp
                            {{ $inUse }} workflow(s)
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="text-center text-muted py-4">Nothing registered.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
