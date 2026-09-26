<?php
namespace App\Http\Controllers\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
class UploadController extends AdminController {
    // Presigned direct-upload untuk S3; fallback: upload biasa untuk local/public.
    public function presign(Request $r){
        $r->validate(['name'=>'required','mime'=>'nullable','folder_id'=>'nullable']);
        $disk=config('lindu.media.disk','public');
        $ext=pathinfo($r->name,PATHINFO_EXTENSION);
        $path='media/'.date('Y/m').'/'.Str::random(28).($ext?".{$ext}":'');
        if(in_array($disk,['s3'])){
            try{
                $s=\Illuminate\Support\Facades\Storage::disk('s3');
                $cmd=$s->getClient()->getCommand('PutObject',['Bucket'=>config('filesystems.disks.s3.bucket'),'Key'=>$path,'ContentType'=>$r->mime,'ACL'=>'public-read']);
                $req=$s->getClient()->createPresignedRequest($cmd,'+15 minutes');
                return $this->ok(['mode'=>'s3','url'=>(string)$req->getUri(),'path'=>$path,'disk'=>'s3']);
            }catch(\Throwable $e){ return response()->json(['ok'=>false,'message'=>$e->getMessage()],500); }
        }
        return $this->ok(['mode'=>'local','post'=>route('admin.media.store'),'path'=>$path]);
    }
    public function confirm(Request $r){
        $d=$r->validate(['path'=>'required','disk'=>'nullable','original_name'=>'nullable','mime'=>'nullable','size'=>'nullable|integer']);
        $row=\App\Models\MediaFile::create(['tenant_id'=>tenant_id(),'folder_id'=>$r->folder_id,'disk'=>$d['disk']??config('lindu.media.disk','public'),'path'=>$d['path'],'filename'=>basename($d['path']),'original_name'=>$d['original_name']??basename($d['path']),'mime'=>$d['mime']??null,'size'=>$d['size']??0,'uuid'=>(string)Str::uuid(),'status'=>'processing']);
        try{ \App\Jobs\ProcessMediaJob::dispatch($row->id); }catch(\Throwable $e){}
        return $this->ok($row);
    }
}
