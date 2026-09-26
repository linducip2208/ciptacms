<?php

namespace App\Http\Controllers\Api\V1;

use App\Core\Services\MediaService;
use App\Models\MediaFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MediaApiController extends ApiController
{
    public function __construct(protected MediaService $media) {}

    public function index(Request $r)
    {
        $q = MediaFile::query()->with('folder');

        if ($search = $r->get('search')) {
            $q->where(function ($w) use ($search) {
                $w->where('original_name', 'like', "%{$search}%")
                    ->orWhere('title', 'like', "%{$search}%")
                    ->orWhere('alt', 'like', "%{$search}%");
            });
        }
        if ($type = $r->get('type')) {
            match ($type) {
                'image' => $q->images(),
                'video' => $q->videos(),
                'document' => $q->documents(),
                default => null,
            };
        }
        if ($folder = $r->get('folder_id')) {
            $q->where('folder_id', $folder);
        }

        $perPage = min(100, max(1, (int) $r->get('per_page', 24)));

        return $this->paginated($q->latest()->paginate($perPage));
    }

    public function show($id)
    {
        return $this->data($this->present(MediaFile::findOrFail($id)));
    }

    public function store(Request $r)
    {
        $r->validate([
            'files' => 'required|array|min:1',
            'files.*' => 'required|file|max:'.((int) setting('media.max_upload_mb', 10) * 1024),
        ]);

        $stored = [];
        $rejected = [];

        foreach ((array) $r->file('files', []) as $f) {
            try {
                $stored[] = $this->present($this->media->store($f, $r->get('folder_id'), tenant_id(), true));
            } catch (\Throwable $e) {
                $rejected[] = ['name' => $f->getClientOriginalName(), 'reason' => $e->getMessage()];
            }
        }

        if ($stored === [] && $rejected !== []) {
            return $this->error('No files were accepted.', 422, ['files' => $rejected]);
        }

        return response()->json([
            'data' => $stored,
            'meta' => ['rejected' => $rejected],
        ], 201);
    }

    public function destroy(Request $r, $id)
    {
        $media = MediaFile::findOrFail($id);

        if (! $r->user() || ! $r->user()->can('media.delete')) {
            return $this->error('You do not have permission to delete media.', 403);
        }

        try {
            $disk = Storage::disk($media->disk);
            $disk->delete($media->path);
            foreach ((array) ($media->variants ?? []) as $rel) {
                $disk->delete($rel);
            }
        } catch (\Throwable $e) {
            // Row removal is what matters.
        }

        $media->delete();

        return $this->data(['deleted' => true, 'id' => $id]);
    }

    protected function present(MediaFile $m): array
    {
        return [
            'id' => $m->id,
            'uuid' => $m->uuid,
            'filename' => $m->filename,
            'original_name' => $m->original_name,
            'mime' => $m->mime,
            'size' => $m->size,
            'width' => $m->width,
            'height' => $m->height,
            'alt' => $m->alt,
            'title' => $m->title,
            'caption' => $m->caption,
            'url' => $this->media->url($m),
            'variants' => $m->variants,
            'folder_id' => $m->folder_id,
            'created_at' => optional($m->created_at)->toIso8601String(),
        ];
    }
}
