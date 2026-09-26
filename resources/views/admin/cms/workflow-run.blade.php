@extends('admin.layout')
@section('title', 'Workflow Run #'.$run->id)
@section('crumb', 'Workflow / Runs / #'.$run->id)

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="mb-1">Run #{{ $run->id }} — {{ $run->workflow?->name ?? 'deleted workflow' }}</h2>
        <div class="text-muted">
            <span class="badge bg-{{ $run->status === 'completed' ? 'green' : ($run->status === 'failed' ? 'red' : 'secondary') }}">{{ $run->status }}</span>
            · {{ $run->created_at?->diffForHumans() }}
        </div>
    </div>
    <div class="d-flex gap-2">
        @if($run->status === 'failed')
            <form method="POST" action="{{ route('admin.cms.workflows.runs.retry', $run) }}">@csrf
                <button class="btn btn-primary">Retry run</button>
            </form>
        @endif
        <a href="{{ route('admin.cms.workflows.runs') }}" class="btn btn-outline">← All runs</a>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header"><h3 class="card-title">Payload</h3></div>
            <div class="card-body">
                <pre class="font-monospace text-xs mb-0" style="max-height:420px;overflow:auto;white-space:pre-wrap">{{ json_encode($run->payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header"><h3 class="card-title">Log</h3></div>
            <div class="card-body">
                @if($run->log)
                    <pre class="font-monospace text-xs mb-0" style="white-space:pre-wrap">{{ $run->log }}</pre>
                @else
                    <p class="text-muted mb-0">No log output for this run.</p>
                @endif

                @if($run->workflow)
                    <hr>
                    <h4>Definition</h4>
                    <dl class="row mb-0">
                        <dt class="col-3">Trigger</dt><dd class="col-9"><code>{{ $run->workflow->trigger_event }}</code></dd>
                        <dt class="col-3">Conditions</dt>
                        <dd class="col-9"><pre class="text-xs mb-0" style="white-space:pre-wrap">{{ json_encode($run->workflow->conditions, JSON_PRETTY_PRINT) }}</pre></dd>
                        <dt class="col-3">Actions</dt>
                        <dd class="col-9"><pre class="text-xs mb-0" style="white-space:pre-wrap">{{ json_encode($run->workflow->actions, JSON_PRETTY_PRINT) }}</pre></dd>
                    </dl>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
