<?php
namespace App\Http\Controllers\Admin;
use Illuminate\Http\Request; use App\Models\{MediaFile,MediaFolder}; use App\Core\Services\MediaService;
class MediaController extends AdminController {
    public function index(Request $r){ $q=MediaFile::latest(); if($s=$r->get('search')) $q->where('original_name','like',"%{$s}%"); if($f=$r->get('folder_id')) $q->where('folder_id',$f); $files=$q->paginate(24); $folders=MediaFolder::all(); return view('admin.media.index',compact('files','folders')); }
    public function store(Request $r, MediaService $svc){ $r->validate(['files.*'=>'required|file|max:10240']); foreach((array)$r->file('files',[]) as $f) $svc->store($f,$r->get('folder_id'),tenant_id(), true); return back()->with('ok','Uploaded — thumbnails/WebP/AVIF diproses di queue'); }
    public function destroy(MediaFile $media){ try{ $disk=\Illuminate\Support\Facades\Storage::disk($media->disk); $disk->delete($media->path); foreach(($media->variants??[]) as $rel) $disk->delete($rel);}catch(\Throwable $e){} $media->delete(); return back()->with('ok','Deleted'); }
    public function reprocess(MediaFile $media, MediaService $svc){ $svc->process($media); return back()->with('ok','Variants regenerated: '.count($media->fresh()->variants??[])); }
    public function thumb(MediaFile $media, string $variant, MediaService $svc){ $url=$svc->url($media,$variant); if($url===$svc->url($media)) abort(404); return redirect($url); }
}
