@extends('admin.layout')
@section('title', 'Form Notifications')
@section('crumb', 'Forms / Email Notifications')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="mb-1">Form notifications</h2>
        <div class="text-muted">
            Templates fired when a form is submitted. Use <code>&#123;&#123;variable&#125;&#125;</code> placeholders —
            the submission payload is available under <code>data</code>.
        </div>
    </div>
    <a href="{{ route('admin.cms.submissions.index') }}" class="btn btn-outline">← Submissions</a>
</div>

<div class="row g-3">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><h3 class="card-title">New notification</h3></div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.cms.form-notifications.store') }}">
                    @csrf
                    <div class="form-group"><label>Name *</label><input name="name" value="{{ old('name') }}" class="form-control" required></div>
                    <div class="form-group"><label>Slug *</label><input name="slug" value="{{ old('slug') }}" class="form-control" required placeholder="form.notification"></div>
                    <div class="form-group">
                        <label>Channel</label>
                        <select name="channel" class="form-control">
                            @foreach($channels as $k => $l)<option value="{{ $k }}">{{ $l }}</option>@endforeach
                        </select>
                    </div>
                    <div class="form-group"><label>Subject</label><input name="subject" value="{{ old('subject') }}" class="form-control"></div>
                    <div class="form-group"><label>Body</label><textarea name="body" rows="6" class="form-control">{{ old('body') }}</textarea></div>
                    <div class="form-group">
                        <label>Variables</label>
                        <textarea name="variables" rows="3" class="form-control font-monospace" placeholder="One per line"></textarea>
                    </div>
                    <button class="btn btn-primary">Create notification</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card">
            <div class="table-responsive">
                <table class="table table-vcenter card-table">
                    <thead><tr><th>Name</th><th>Slug</th><th>Channel</th><th>Subject</th><th></th></tr></thead>
                    <tbody>
                        @forelse($rows as $t)
                            <tr>
                                <td><b>{{ $t->name }}</b></td>
                                <td><code>{{ $t->slug }}</code></td>
                                <td><span class="badge">{{ $channels[$t->channel] ?? $t->channel }}</span></td>
                                <td class="text-muted small">{{ \Illuminate\Support\Str::limit($t->subject, 44) }}</td>
                                <td>
                                    <form method="POST" action="{{ route('admin.cms.form-notifications.destroy', $t) }}"
                                          onsubmit="return confirm('Delete this notification?')">@csrf @method('DELETE')
                                        <button class="text-rose-600">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted py-4">
                                No form notifications yet. The admin address that receives them is set under
                                <a href="{{ route('admin.settings.tab', 'notifications') }}">Settings → Notifications</a>.
                            </td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
