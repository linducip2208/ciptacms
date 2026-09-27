@extends('admin.layout')
@section('title','Queue Monitor')@section('crumb','System / Queue')
@section('content')
<div class="row row-cards mb-3">
<div class="col-sm-6 col-lg-3"><div class="card"><div class="card-body"><div class="text-muted small">Pending jobs</div><div class="h2 m-0">{{ $pending }}</div></div></div></div>
<div class="col-sm-6 col-lg-3"><div class="card"><div class="card-body"><div class="text-muted small">Failed jobs</div><div class="h2 m-0">{{ $failed }}</div><form method="POST" action="{{ route('admin.queue.flush') }}" class="mt-1">@csrf<button class="btn btn-sm btn-outline-danger" onclick="return confirm('Clear all failed?')">Flush</button></form></div></div></div>
<div class="col-lg-6"><div class="card"><div class="card-body">Horizon: {!! $horizon?'<span class="badge bg-green">installed</span>':'<span class="badge bg-yellow">DB driver (Horizon-ready)</span>' !!}<div class="text-muted small mt-1">Jalankan <code>php artisan queue:work</code>. Untuk Redis+Horizon: <code>composer require laravel/horizon && php artisan horizon:install</code>.</div></div></div></div>
</div>
<div class="row row-cards">
<div class="col-lg-6"><div class="card"><div class="card-header"><h3 class="card-title">Recent jobs</h3></div><div class="table-responsive"><table class="table"><thead><tr><th>ID</th><th>Queue</th><th>Attempts</th></tr></thead><tbody>@foreach($recent as $j)<tr><td>{{ $j->id }}</td><td>{{ $j->queue }}</td><td>{{ $j->attempts }}</td></tr>@endforeach
@include('admin.partials.empty-row', [
    'count' => $recent->count(),
    'attributes' => new \Illuminate\View\ComponentAttributeBag(['colspan' => 3]),
    'icon' => 'ti-stack',
    'title' => 'Queue is empty',
    'message' => 'No jobs are waiting. This fills up when webhooks, media processing and scheduled exports are dispatched.',
])
</tbody></table></div></div></div>
<div class="col-lg-6"><div class="card"><div class="card-header"><h3 class="card-title">Failed jobs</h3></div><div class="table-responsive"><table class="table"><thead><tr><th>ID</th><th>Exception</th><th></th></tr></thead><tbody>@foreach($fails as $f)<tr><td>{{ $f->id }}</td><td class="small text-truncate" style="max-width:280px">{{ $f->exception }}</td><td><form method="POST" action="{{ route('admin.queue.retry',$f->id) }}">@csrf<button class="btn btn-sm btn-outline-primary">Retry</button></form></td></tr>@endforeach
@include('admin.partials.empty-row', [
    'count' => $fails->count(),
    'attributes' => new \Illuminate\View\ComponentAttributeBag(['colspan' => 3]),
    'icon' => 'ti-alert-triangle',
    'title' => 'No failed jobs',
    'message' => 'Nothing needs a retry. A job only lands here after it has exhausted every attempt.',
])
</tbody></table></div></div></div>
</div>
@endsection
