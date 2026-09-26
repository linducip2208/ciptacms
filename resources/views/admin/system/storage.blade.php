@extends('admin.layout')
@section('title', 'Storage')
@section('crumb', 'System / Storage')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="mb-1">Storage</h2>
        <div class="text-muted">{{ $mediaCount }} file(s) in the media library.</div>
    </div>
    <a href="{{ route('admin.media.folders') }}" class="btn btn-outline">Media folders</a>
</div>

<div class="card mb-3">
    <div class="card-header"><h3 class="card-title">Filesystem disks</h3></div>
    <div class="table-responsive">
        <table class="table table-vcenter card-table">
            <thead><tr><th>Disk</th><th>Root</th><th>Reachable</th><th>Files</th></tr></thead>
            <tbody>
                @foreach($disks as $d)
                    <tr>
                        <td><code>{{ $d['name'] }}</code></td>
                        <td class="text-muted text-xs"><div class="text-truncate-cell">{{ $d['root'] }}</div></td>
                        <td>
                            <span class="badge bg-{{ $d['exists'] ? 'green' : 'red' }}">
                                {{ $d['exists'] ? 'yes' : 'no' }}
                            </span>
                        </td>
                        <td>{{ number_format($d['files']) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<div class="card">
    <div class="card-header"><h3 class="card-title">Directory usage</h3></div>
    <div class="table-responsive">
        <table class="table table-vcenter card-table">
            <thead><tr><th>Path</th><th>Exists</th><th>Writable</th><th>Size</th></tr></thead>
            <tbody>
                @foreach($usage as $u)
                    <tr>
                        <td><code>{{ $u['path'] }}</code></td>
                        <td>
                            <span class="badge bg-{{ $u['exists'] ? 'green' : 'red' }}">{{ $u['exists'] ? 'yes' : 'no' }}</span>
                        </td>
                        <td>
                            <span class="badge bg-{{ $u['writable'] ? 'green' : 'red' }}">{{ $u['writable'] ? 'yes' : 'no' }}</span>
                        </td>
                        <td>{{ $u['size'] > 1048576 ? number_format($u['size'] / 1048576, 1).' MB' : number_format($u['size'] / 1024, 1).' KB' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="card-footer text-muted small">
        Uploads are written by the operator only through the Media Library. Nothing in the admin UI
        accepts an arbitrary filesystem path.
    </div>
</div>
@endsection
