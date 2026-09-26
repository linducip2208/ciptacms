<?php

namespace App\Http\Controllers\Admin;

use App\Core\Services\MediaService;
use App\Models\MediaFile;
use App\Models\MediaFolder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MediaController extends AdminController
{
    public function index(Request $r)
    {
        $q = MediaFile::query()->with('folder');

        if ($search = $r->get('search')) {
            $q->where(function ($w) use ($search) {
                $w->where('original_name', 'like', "%{$search}%")
                    ->orWhere('filename', 'like', "%{$search}%")
                    ->orWhere('title', 'like', "%{$search}%")
                    ->orWhere('alt', 'like', "%{$search}%");
            });
        }
        if ($folder = $r->get('folder_id')) {
            $q->where('folder_id', $folder);
        }
        if ($type = $r->get('type')) {
            match ($type) {
                'image' => $q->images(),
                'video' => $q->videos(),
                'audio' => $q->audio(),
                'document' => $q->documents(),
                default => null,
            };
        }
        if ($sort = $r->get('sort')) {
            $dir = $r->get('dir') === 'asc' ? 'asc' : 'desc';
            $q->orderBy($sort, $dir);
        } else {
            $q->latest();
        }

        return view('admin.media.index', [
            'files' => $q->paginate(24)->withQueryString(),
            'folders' => MediaFolder::withCount('files')->orderBy('name')->get(),
            'counts' => [
                'all' => MediaFile::count(),
                'image' => MediaFile::images()->count(),
                'document' => MediaFile::documents()->count(),
                'video' => MediaFile::videos()->count(),
            ],
        ]);
    }

    public function store(Request $r, MediaService $svc)
    {
        $r->validate([
            'files' => 'required|array|min:1',
            'files.*' => 'required|file|max:'.((int) setting('media.max_upload_mb', config('lindu.media.max_upload_mb', 10)) * 1024),
        ]);

        $stored = 0;
        $rejected = [];

        foreach ((array) $r->file('files', []) as $f) {
            try {
                $svc->store($f, $r->get('folder_id'), tenant_id(), true);
                $stored++;
            } catch (\Throwable $e) {
                $rejected[] = $f->getClientOriginalName().': '.$e->getMessage();
            }
        }

        $msg = "Uploaded {$stored} file(s). Thumbnails and WebP/AVIF run on the queue.";
        if ($rejected) {
            return back()->with('ok', $msg)->withErrors(['files' => $rejected]);
        }

        return back()->with('ok', $msg);
    }

    public function update(Request $r, MediaFile $media)
    {
        $data = $r->validate([
            'alt' => 'nullable|string|max:255',
            'title' => 'nullable|string|max:190',
            'caption' => 'nullable|string|max:500',
            'description' => 'nullable|string',
            'folder_id' => 'nullable|exists:media_folders,id',
        ]);

        $media->update($data);
        $this->audit('update_media', $media, $r);

        return back()->with('ok', 'Media details saved');
    }

    public function destroy(Request $r, MediaFile $media)
    {
        try {
            $disk = Storage::disk($media->disk);
            $disk->delete($media->path);
            foreach ((array) ($media->variants ?? []) as $rel) {
                $disk->delete($rel);
            }
        } catch (\Throwable $e) {
            // The row is what matters; a missing file on disk is not fatal.
        }

        $media->delete();
        $this->audit('delete_media', $media, $r);

        return back()->with('ok', 'File deleted');
    }

    public function restore(Request $r, $id)
    {
        $media = MediaFile::onlyTrashed()->findOrFail($id);
        $media->restore();
        $this->audit('restore_media', $media, $r);

        return back()->with('ok', 'File restored');
    }

    public function reprocess(Request $r, MediaFile $media, MediaService $svc)
    {
        $svc->process($media);
        $this->audit('reprocess_media', $media, $r);

        return back()->with('ok', 'Regenerated '.count((array) ($media->fresh()->variants ?? [])).' variant(s)');
    }

    public function thumb(MediaFile $media, string $variant, MediaService $svc)
    {
        $url = $svc->url($media, $variant);
        if ($url === $svc->url($media)) {
            abort(404, 'No such variant.');
        }

        return redirect($url);
    }

    // ---- Folders ------------------------------------------------------

    public function folders(Request $r)
    {
        $q = MediaFolder::withCount('files')->withSum('files', 'size');

        if ($search = $r->get('search')) {
            $q->where('name', 'like', "%{$search}%");
        }

        return view('admin.media.folders', [
            'rows' => $q->orderBy('name')->paginate(30)->withQueryString(),
            'rootCount' => MediaFile::whereNull('folder_id')->count(),
        ]);
    }

    public function storeFolder(Request $r)
    {
        $data = $r->validate([
            'name' => 'required|string|max:120',
            'parent_id' => 'nullable|exists:media_folders,id',
            'description' => 'nullable|string|max:255',
        ]);

        $folder = MediaFolder::create($data);
        $this->audit('create_media_folder', $folder, $r);

        return back()->with('ok', "Folder '{$folder->name}' created");
    }

    public function updateFolder(Request $r, MediaFolder $folder)
    {
        $data = $r->validate([
            'name' => 'required|string|max:120',
            'parent_id' => 'nullable|exists:media_folders,id',
            'description' => 'nullable|string|max:255',
        ]);

        if ($data['parent_id'] == $folder->id) {
            return back()->withErrors(['msg' => 'A folder cannot be its own parent.']);
        }

        $folder->update($data);
        $this->audit('update_media_folder', $folder, $r);

        return back()->with('ok', 'Folder updated');
    }

    public function destroyFolder(Request $r, MediaFolder $folder)
    {
        $count = $folder->files()->count();
        if ($count > 0) {
            return back()->withErrors(['msg' => "This folder still holds {$count} file(s). Move or delete them first."]);
        }
        if ($folder->children()->exists()) {
            return back()->withErrors(['msg' => 'This folder has sub-folders. Remove them first.']);
        }

        $folder->delete();
        $this->audit('delete_media_folder', $folder, $r);

        return back()->with('ok', 'Folder deleted');
    }

    // ---- Storage ------------------------------------------------------

    public function storage()
    {
        $disks = [];
        foreach (['local', 'public', 's3'] as $name) {
            try {
                $disk = Storage::disk($name);
                $disks[] = [
                    'name' => $name,
                    'root' => method_exists($disk, 'path') ? $disk->path('') : '(remote)',
                    'available' => true,
                    'files' => rescue(fn () => count($disk->allFiles()), 0),
                    'bytes' => rescue(fn () => $this->sum($disk), 0),
                    'active' => config('lindu.media.disk') === $name,
                ];
            } catch (\Throwable $e) {
                $disks[] = ['name' => $name, 'root' => '—', 'available' => false, 'files' => 0, 'bytes' => 0, 'active' => false];
            }
        }

        return view('admin.media.storage', [
            'disks' => $disks,
            'maxUploadMb' => (int) setting('media.max_upload_mb', config('lindu.media.max_upload_mb', 10)),
            'allowedMimes' => config('lindu.media.allowed_mimes', []),
            'thumbs' => config('lindu.media.thumbnails', []),
            'autoOptimize' => (bool) setting('media.auto_optimize', true),
        ]);
    }

    protected function sum($disk): int
    {
        $total = 0;
        foreach ($disk->allFiles() as $file) {
            $total += $disk->size($file);
        }

        return $total;
    }
}
