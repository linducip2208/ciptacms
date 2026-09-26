@extends('admin.layout')
@section('title','Media')@section('crumb','Media')
@section('content')
<div class="card mb-4"><form method="POST" action="{{ route('admin.media.store') }}" enctype="multipart/form-data" class="d-flex gap-2">@csrf<input type="file" name="files[]" multiple class="form-control"><button class="btn btn-primary">Upload</button></form><p class="text-muted small mt-1">Thumbnails 150/300/800 + WebP + AVIF (jika GD support) diproses via queue <code>ProcessMediaJob</code>.</p></div>
<div class="row row-cards">@foreach($files as $f)<div class="card p-2 text-xs">
@if(str_starts_with($f->mime,'image'))<img src="{{ app(App\Core\Services\MediaService::class)->url($f, '150x150') }}" loading="lazy" class="rounded w-full h-28 object-cover mb-1" onerror="this.style.display='none'">@endif
<div class="truncate font-medium">{{ $f->original_name }}</div><div class="text-slate-500">{{ $f->mime }} · {{ round($f->size/1024) }}KB · {{ $f->status }}</div>
@if($f->variants)<div class="text-[11px] text-emerald-700">{{ count($f->variants) }} variants: {{ implode(', ',array_keys($f->variants)) }}</div>@endif
<div class="d-flex gap-2 mt-1"><form method="POST" action="{{ route('admin.media.reprocess',$f) }}">@csrf<button class="text-indigo-600">Reprocess</button></form><form method="POST" action="{{ route('admin.media.destroy',$f) }}">@csrf @method('DELETE')<button class="text-rose-600">Delete</button></form></div>
</div>@endforeach</div><div class="mt-3">{{ $files->links() }}</div>
@endsection
