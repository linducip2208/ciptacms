@extends('admin.layout')
@section('title', 'Notification Templates')
@section('crumb', 'Notifications / Templates')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="mb-1">Templates</h2>
        <div class="text-muted">Use <code>&#123;&#123;variable&#125;&#125;</code> placeholders; the sending code substitutes them.</div>
    </div>
    <a href="{{ route('admin.notifications.index') }}" class="btn btn-outline">← Delivery log</a>
</div>

<div class="row g-3">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><h3 class="card-title">New template</h3></div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.notifications.templates.store') }}">
                    @csrf
                    <div class="form-group"><label>Name *</label><input name="name" value="{{ old('name') }}" class="form-control" required></div>
                    <div class="form-group"><label>Slug *</label><input name="slug" value="{{ old('slug') }}" class="form-control" required placeholder="contact-received"></div>
                    <div class="form-group">
                        <label>Channel</label>
                        <select name="channel" class="form-control">
                            @foreach($channels as $k => $l)<option value="{{ $k }}">{{ $l }}</option>@endforeach
                        </select>
                    </div>
                    <div class="form-group"><label>Subject</label><input name="subject" value="{{ old('subject') }}" class="form-control" placeholder="New message from &#123;&#123;name&#125;&#125;"></div>
                    <div class="form-group"><label>Body</label><textarea name="body" rows="6" class="form-control">{{ old('body') }}</textarea></div>
                    <div class="form-group">
                        <label>Variables</label>
                        <textarea name="variables" rows="3" class="form-control font-monospace" placeholder="One per line"></textarea>
                    </div>
                    <button class="btn btn-primary">Create template</button>
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
                                    <div class="d-flex gap-2">
                                        <button class="text-indigo-600" data-bs-toggle="modal" data-bs-target="#t-{{ $t->id }}">Edit</button>
                                        <form method="POST" action="{{ route('admin.notifications.templates.destroy', $t) }}"
                                              onsubmit="return confirm('Delete this template?')">@csrf @method('DELETE')
                                            <button class="text-rose-600">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <div class="modal fade" id="t-{{ $t->id }}" tabindex="-1">
                                <div class="modal-dialog modal-lg"><div class="modal-content">
                                    <form method="POST" action="{{ route('admin.notifications.templates.update', $t) }}">
                                        @csrf @method('PUT')
                                        <div class="modal-header">
                                            <h5 class="modal-title">Edit {{ $t->name }}</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="row">
                                                <div class="form-group col-6"><label>Name</label><input name="name" value="{{ $t->name }}" class="form-control" required></div>
                                                <div class="form-group col-6"><label>Slug</label><input name="slug" value="{{ $t->slug }}" class="form-control" required></div>
                                            </div>
                                            <div class="form-group">
                                                <label>Channel</label>
                                                <select name="channel" class="form-control">
                                                    @foreach($channels as $k => $l)
                                                        <option value="{{ $k }}" @selected($t->channel === $k)>{{ $l }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="form-group"><label>Subject</label><input name="subject" value="{{ $t->subject }}" class="form-control"></div>
                                            <div class="form-group"><label>Body</label><textarea name="body" rows="6" class="form-control">{{ $t->body }}</textarea></div>
                                            <div class="form-group">
                                                <label>Variables</label>
                                                <textarea name="variables" rows="3" class="form-control font-monospace">{{ is_array($t->variables) ? implode("\n", $t->variables) : $t->variables }}</textarea>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-outline" data-bs-dismiss="modal">Cancel</button>
                                            <button class="btn btn-primary">Save</button>
                                        </div>
                                    </form>
                                </div></div>
                            </div>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted py-4">No templates yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
