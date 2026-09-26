@extends('admin.layout')
@section('title', 'Developer — Events & Hooks')
@section('crumb', 'Developer')

@section('content')
<ul class="nav nav-pills mb-3">
    <li class="nav-item"><a class="nav-link active" href="{{ route('admin.developer.events') }}">Events</a></li>
    <li class="nav-item"><a class="nav-link" href="{{ route('admin.developer.hooks') }}">Hooks</a></li>
    <li class="nav-item"><a class="nav-link" href="{{ route('admin.api-docs') }}">API</a></li>
    <li class="nav-item"><a class="nav-link" href="{{ route('admin.cms.webhooks.index') }}">Webhooks</a></li>
</ul>

<div class="row g-3">
    <div class="col-lg-5">
        <div class="card mb-3">
            <div class="card-header"><h3 class="card-title">Dispatch an event</h3></div>
            <div class="card-body">
                <p class="text-muted small">
                    Fires matching webhooks and workflows. Useful for testing integrations before going live.
                </p>
                <form method="POST" action="{{ route('admin.developer.events.dispatch') }}">
                    @csrf
                    <div class="form-group">
                        <label>Event</label>
                        <select name="event" class="form-control">
                            @foreach($catalog as $name => $label)
                                <option value="{{ $name }}">{{ $label }} <code>{{ $name }}</code></option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Payload (JSON)</label>
                        <textarea name="payload" rows="7" class="form-control font-monospace">{
  "id": 1,
  "name": "Test"
}</textarea>
                    </div>
                    <button class="btn btn-primary">Dispatch</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card mb-3">
            <div class="card-header"><h3 class="card-title">Event catalog</h3></div>
            <div class="table-responsive">
                <table class="table table-vcenter card-table">
                    <thead><tr><th>Event</th><th>Description</th><th>Webhooks</th></tr></thead>
                    <tbody>
                        @foreach($catalog as $name => $label)
                            <tr>
                                <td><code>{{ $name }}</code></td>
                                <td class="text-muted">{{ $label }}</td>
                                <td>{{ $webhooks->where('event', $name)->count() }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h3 class="card-title">Laravel listeners ({{ count($registered) }})</h3></div>
            <div class="table-responsive" style="max-height:340px;overflow:auto">
                <table class="table table-vcenter card-table">
                    <thead><tr><th>Event</th><th>Listeners</th></tr></thead>
                    <tbody>
                        @forelse($registered as $name => $count)
                            <tr><td><code>{{ $name }}</code></td><td>{{ $count }}</td></tr>
                        @empty
                            <tr><td colspan="2" class="text-center text-muted py-4">No listeners registered.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
