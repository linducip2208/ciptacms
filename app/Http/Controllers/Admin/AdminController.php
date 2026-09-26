<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Core\Services\AuditService;
use Illuminate\Http\Request;
class AdminController extends Controller {
    protected function ok($data=[], $msg='OK'){ return response()->json(['ok'=>true,'message'=>$msg,'data'=>$data]); }
    protected function audit(string $action, $model, Request $r){ try{ app(AuditService::class)->log($action,$model,['user_id'=>$r->user()?->id]); }catch(\Throwable $e){} }
}
