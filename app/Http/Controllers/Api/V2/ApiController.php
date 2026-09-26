<?php
namespace App\Http\Controllers\Api\V2;
use App\Http\Controllers\Controller;
class ApiController extends Controller {
    protected function data($data, array $meta=['version'=>'v2']){ return response()->json(['data'=>$data,'meta'=>$meta]); }
    protected function paginated($p, array $extra=[]){ return response()->json(['data'=>$p->items(),'meta'=>array_merge(['version'=>'v2','current_page'=>$p->currentPage(),'total'=>$p->total(),'per_page'=>$p->perPage()],$extra)]); }
    protected function error(string $msg, int $code=422, $errors=[]){ return response()->json(['message'=>$msg,'errors'=>$errors,'meta'=>['version'=>'v2']],$code); }
    protected function sparse($row, ?string $fields): array {
        $a=$row instanceof \Illuminate\Database\Eloquent\Model ? $row->toArray() : (array)$row;
        if(!$fields) return $a;
        $only=array_intersect(explode(',',$fields),array_keys($a));
        return array_intersect_key($a,array_flip($only));
    }
}
