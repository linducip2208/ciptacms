@extends('admin.layout')
@section('title', 'Submission #'.$submission->id)
@section('crumb', 'Forms / Submissions / #'.$submission->id)

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="mb-1">Submission #{{ $submission->id }}</h2>
        <div class="text-muted">
            {{ $submission->form?->title ?? 'form deleted' }} ·
            {{ $submission->created_at?->diffForHumans() }}
        </div>
    </div>
    <div class="d-flex gap-2">
        @if($submission->form)
            <a href="{{ route('admin.cms.forms.builder', $submission->form) }}" class="btn btn-outline">Edit form</a>
        @endif
        <a href="{{ route('admin.cms.submissions.index') }}" class="btn btn-outline">← All</a>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Submitted values</h3></div>
            <div class="table-responsive">
                <table class="table table-vcenter card-table">
                    <thead><tr><th>Field</th><th>Value</th></tr></thead>
                    <tbody>
                        @php
                            $labels = $submission->form
                                ? $submission->form->fields->pluck('label', 'name')
                                : collect();
                        @endphp
                        @foreach((array) $submission->data as $key => $value)
                            <tr>
                                <td><b>{{ $labels[$key] ?? $key }}</b><div class="text-muted text-xs">{{ $key }}</div></td>
                                <td>
                                    @if(is_array($value))
                                        <ul style="margin:0;padding-left:18px">
                                            @foreach($value as $v)<li>{{ is_scalar($v) ? $v : json_encode($v) }}</li>@endforeach
                                        </ul>
                                    @elseif(preg_match('#^(/|\w+://)#', (string) $value) && ! is_numeric($value))
                                        <a href="{{ $value }}" target="_blank" rel="noopener">{{ $value }}</a>
                                    @else
                                        {{ $value }}
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Request</h3></div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-4">IP</dt><dd class="col-8">{{ $submission->ip ?: '—' }}</dd>
                    <dt class="col-4">Received</dt>
                    <dd class="col-8">{{ optional($submission->created_at)->toDateTimeString() }}</dd>
                    <dt class="col-4">User agent</dt>
                    <dd class="col-8 text-xs" style="word-break:break-all">{{ $submission->user_agent ?: '—' }}</dd>
                </dl>
            </div>
            <div class="card-footer">
                <form method="POST" action="{{ route('admin.cms.submissions.destroy', $submission) }}"
                      onsubmit="return confirm('Delete this submission?')">@csrf @method('DELETE')
                    <button class="btn btn-outline w-100 text-rose-600">Delete submission</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
