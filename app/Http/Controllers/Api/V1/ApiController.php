<?php
namespace App\Http\Controllers\Api\V1;
use App\Http\Controllers\Controller;
class ApiController extends Controller {
    protected function data($data, $meta=[]){ return response()->json(['data'=>$data,'meta'=>$meta]); }
    protected function paginated($p){ return response()->json(['data'=>$p->items(),'meta'=>['current_page'=>$p->currentPage(),'total'=>$p->total(),'per_page'=>$p->perPage()]]); }
    protected function error(string $msg, int $code=422, $errors=[]){ return response()->json(['message'=>$msg,'errors'=>$errors],$code); }
}
