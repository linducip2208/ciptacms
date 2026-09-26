@extends('admin.layout')
@section('title', 'Media Storage')
@section('crumb', 'Media / Storage')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="mb-1">Storage</h2>
        <div class="text-muted">Uploads are written by the CMS only. No admin form accepts a raw filesystem path.</div>
    </div>
    <a href="{{ route('admin.media.index') }}" class="btn btn-outline">← Library</a>
</div>

<div class="card mb-3">
    <div class="card-header"><h3 class="card-title">Disks</h3></div>
    <div class="table-responsive">
        <table class="table table-vcenter card-table">
            <thead><tr><th>Disk</th><th>Active</th><th>Available</th><th>Files</th><th>Size</th><th>Root</th></tr></thead>
            <tbody>
                @foreach($disks as $d)
                    <tr>
                        <td><code>{{ $d['name'] }}</code></td>
                        <td>@if($d['active'])<span class="badge bg-blue">in use</span>@else — @endif</td>
                        <td>
                            <span class="badge bg-{{ $d['available'] ? 'green' : 'red' }}">{{ $d['available'] ? 'yes' : 'no' }}</span>
                        </td>
                        <td>{{ number_format($d['files']) }}</td>
                        <td class="text-muted small">
                            {{ $d['bytes'] > 1048576 ? number_format($d['bytes'] / 1048576, 1).' MB' : number_format($d['bytes'] / 1024, 1).' KB' }}
                        </td>
                        <td class="text-muted text-xs" style="max-width:260px">
                            <div class="text-truncate-cell">{{ $d['root'] }}</div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="card-footer">
        <a href="{{ route('admin.settings.tab', 'storage') }}" class="btn btn-outline btn-sm">Change the active disk</a>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header"><h3 class="card-title">Upload limits</h3></div>
            <div class="card-body">
                <p class="mb-1"><b>{{ $maxUploadMb }} MB</b> per file</p>
                <p class="mb-1"><b>{{ $autoOptimize ? 'On' : 'Off' }}</b> automatic WebP/AVIF</p>
                <h4 class="mt-3 mb-2">Allowed types</h4>
                <div class="d-flex gap-1 flex-wrap">
                    @foreach($allowedMimes as $m)
                        <span class="badge">{{ $m }}</span>
                    @endforeach
                </div>
                <p class="text-muted small mt-3 mb-0">
                    Uploads are rejected unless the extension is on this list <em>and</em> the detected
                    MIME type matches. Rename-in-disguise uploads do not get through.
                </p>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card h-100">
            <div class="card-header"><h3 class="card-title">Thumbnail variants</h3></div>
            <div class="table-responsive">
                <table class="table table-vcenter card-table">
                    <thead><tr><th>Size</th><th>Generated URL</th></tr></thead>
                    <tbody>
                        @forelse($thumbs as [$w, $h])
                            <tr>
                                <td><code>{{ $w }}×{{ $h }}</code></td>
                                <td class="text-muted text-xs">{{ url('/admin/media/1/thumb/'.$w.'x'.$h) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="2" class="text-center text-muted py-3">No thumbnail sizes configured.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
