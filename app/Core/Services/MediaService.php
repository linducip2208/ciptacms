<?php
namespace App\Core\Services;
use App\Models\MediaFile;
use App\Jobs\ProcessMediaJob;
use App\Core\Services\Quota;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
class MediaService {
    public function store(UploadedFile $file, ?string $folderId=null, ?string $tenantId=null, bool $async=true): MediaFile {
        $max = (int)config('lindu.media.max_upload_mb',10);
        if ($file->getSize() > $max*1024*1024) throw new \RuntimeException("File exceeds {$max}MB");
        $this->assertAllowed($file);

        // Plan cap on uploads. Throws before anything is written to disk.
        Quota::guard('media', 1, $tenantId ? \App\Models\Tenant::find($tenantId) : null);

        $disk = config('lindu.media.disk','public');
        $path = $file->store('media/'.date('Y/m'), $disk);
        $meta = ['original'=>$file->getClientOriginalName(),'mime'=>$file->getMimeType(),'size'=>$file->getSize()];
        [$w,$h]=[null,null];
        if (str_starts_with((string)$file->getMimeType(),'image')) { try { [$w,$h]=getimagesize($file->getRealPath()) ?: [null,null]; } catch(\Throwable $e){} }
        $row = MediaFile::create([
            'tenant_id'=>$tenantId,'folder_id'=>$folderId,'disk'=>$disk,'path'=>$path,
            'filename'=>$file->hashName(),'original_name'=>$file->getClientOriginalName(),
            'mime'=>$file->getMimeType(),'size'=>$file->getSize(),'width'=>$w,'height'=>$h,
            'alt'=>pathinfo($file->getClientOriginalName(),PATHINFO_FILENAME),
            'meta'=>$meta,'uuid'=>(string)Str::uuid(),'status'=>'processing','optimized'=>false,
        ]);
        Quota::consume('media', 1, $tenantId ? \App\Models\Tenant::find($tenantId) : null);
        if ($async && str_starts_with((string)$file->getMimeType(),'image')) {
            try { ProcessMediaJob::dispatch($row->id); } catch(\Throwable $e){ $this->process($row->fresh()); }
        } else {
            $this->process($row);
        }
        return $row->fresh();
    }

    /**
     * Reject anything outside the configured allowlist.
     *
     * The client-supplied extension is never trusted on its own: a `.png`
     * containing PHP is the classic bypass. Both the sniffed MIME type and the
     * extension are checked, and a mismatch between them is refused outright.
     */
    protected function assertAllowed(UploadedFile $file): void
    {
        $allowed = array_map('strtolower', (array) config('lindu.media.allowed_mimes', []));
        if ($allowed === []) {
            return;
        }

        $extension = strtolower((string) $file->getClientOriginalExtension());

        // Strip the sub-type: image/jpeg -> jpeg, application/pdf -> pdf.
        $mime = strtolower((string) $file->getMimeType());
        $detected = str_contains($mime, '/') ? substr($mime, strpos($mime, '/') + 1) : $mime;

        // svg+xml, application/vnd.ms-excel, text/plain etc. are mapped to the
        // extension an operator would actually list in the allowlist.
        $aliases = [
            'svg+xml' => 'svg', 'jpeg' => 'jpg', 'vnd.ms-excel' => 'xls',
            'vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
            'vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
            'vnd.ms-word' => 'doc', 'vnd.oasis.opendocument.text' => 'odt',
            'x-zip-compressed' => 'zip', 'plain' => 'txt', 'csv' => 'csv',
        ];
        $detected = $aliases[$detected] ?? $detected;

        $inList = fn (string $ext) => in_array($ext, $allowed, true);

        if ($inList($extension) && $inList($detected)) {
            return;
        }

        // A declared octet-stream tells us nothing; fall back to the
        // extension, which is all the server can go on for that upload.
        if ($mime === 'application/octet-stream' && $inList($extension)) {
            return;
        }

        throw new \RuntimeException(sprintf(
            'File type not allowed: %s (detected %s). Allowed: %s',
            $extension ?: 'unknown',
            $mime ?: 'unknown',
            implode(', ', $allowed)
        ));
    }

    public function process(MediaFile $f): MediaFile {
        try {
            if (!str_starts_with((string)$f->mime,'image')) { $f->update(['status'=>'ready']); return $f; }
            $disk = Storage::disk($f->disk);
            if (!$disk->exists($f->path)) { $f->update(['status'=>'ready']); return $f; }
            $src = $disk->path($f->path);
            $variants = $f->variants ?? [];
            foreach (config('lindu.media.thumbnails', [[150,150],[300,300],[800,600]]) as $size) {
                [$tw,$th] = $size;
                $key = "{$tw}x{$th}";
                if (isset($variants[$key])) continue;
                $destRel = dirname($f->path)."/thumbs/{$tw}x{$th}_".$f->filename;
                try {
                    $this->resizeGd($src, $disk->path($destRel), $tw, $th);
                    $variants[$key] = $destRel;
                } catch(\Throwable $e){}
            }
            // WebP + AVIF conversions (GD: webp; avif if supported)
            foreach (['webp'=>true,'avif'=>function_exists('imageavif')] as $fmt=>$supported) {
                if (!$supported || isset($variants[$fmt])) continue;
                $destRel = preg_replace('/\.[^.]+$/', ".$fmt", $f->path);
                try { $this->convertGd($src, $disk->path($destRel), $fmt); $variants[$fmt]=$destRel; } catch(\Throwable $e){}
            }
            $f->update(['variants'=>$variants,'optimized'=>true,'status'=>'ready']);
        } catch(\Throwable $e){ try{ $f->update(['status'=>'ready']); }catch(\Throwable $x){} }
        return $f->fresh();
    }
    protected function ensureDir(string $abs): void { $d=dirname($abs); if(!is_dir($d)) mkdir($d,0775,true); }
    protected function openGd(string $src) {
        $info = @getimagesize($src); $mime=$info['mime']??'';
        return match(true){
            str_contains($mime,'jpeg'),str_contains($mime,'jpg')=>imagecreatefromjpeg($src),
            str_contains($mime,'png')=>imagecreatefrompng($src),
            str_contains($mime,'gif')=>imagecreatefromgif($src),
            str_contains($mime,'webp')=>function_exists('imagecreatefromwebp')?imagecreatefromwebp($src):throw new \RuntimeException('webp unsupported'),
            default=>throw new \RuntimeException('unsupported '.$mime),
        };
    }
    protected function resizeGd(string $src, string $dest, int $tw, int $th): void {
        $this->ensureDir($dest);
        $img=$this->openGd($src); $w=imagesx($img); $h=imagesy($img);
        $ratio=min($tw/max(1,$w),$th/max(1,$h)); $nw=max(1,(int)($w*$ratio)); $nh=max(1,(int)($h*$ratio));
        $out=imagecreatetruecolor($nw,$nh);
        imagealphablending($out,false); imagesavealpha($out,true);
        imagecopyresampled($out,$img,0,0,0,0,$nw,$nh,$w,$h);
        imagejpeg($out,$dest,82); imagedestroy($img); imagedestroy($out);
    }
    protected function convertGd(string $src, string $dest, string $fmt): void {
        $this->ensureDir($dest);
        $img=$this->openGd($src);
        if($fmt==='webp'){ if(!function_exists('imagewebp')) throw new \RuntimeException('no webp'); imagewebp($img,$dest,82); }
        else { if(!function_exists('imageavif')) throw new \RuntimeException('no avif'); imageavif($img,$dest,80); }
        imagedestroy($img);
    }
    public function url(MediaFile $f, ?string $variant=null): string {
        $disk = Storage::disk($f->disk);
        $rel = ($variant && isset($f->variants[$variant])) ? $f->variants[$variant] : $f->path;
        // CDN prefix (S3/Cloudfront-ready): MEDIA_CDN_URL=https://cdn.example.com
        if ($cdn=rtrim((string)config('filesystems.cdn_url', env('MEDIA_CDN_URL','')),'/')) {
            if (!$disk->exists($rel) && $rel!==$f->path) $rel=$f->path;
            return $cdn.'/'.ltrim($rel,'/');
        }
        if ($variant && isset($f->variants[$variant]) && $disk->exists($f->variants[$variant])) return $disk->url($f->variants[$variant]);
        // Private disk → signed temporary URL
        try { if(in_array($f->disk,['s3','private'])) return $disk->temporaryUrl($rel, now()->addHour()); } catch(\Throwable $e){}
        return $disk->url($rel);
    }
    public function srcset(MediaFile $f): string {
        $out=[];
        foreach(($f->variants??[]) as $k=>$rel){
            if(preg_match('/^(\d+)x(\d+)$/',$k,$m)) $out[]=$this->url($f,$k)." {$m[1]}w";
        }
        return implode(', ',$out);
    }
}
