<?php
namespace App\Http\Controllers\Api\V2;
use Illuminate\Http\Request;
class ResourceApiController extends ApiController {
    protected array $map=[];
    public function __construct(){ $this->map=config('lindu_admin.resources',[]); }
    protected function applyIncludes($q, $model, ?string $inc): void {
        if(!$inc) return;
        foreach(explode(',',$inc) as $rel){ $rel=trim($rel); if($rel && method_exists($model,'isRelation')===false){ try{ $q->with($rel); }catch(\Throwable $e){} } }
    }
    public function index(Request $r, string $resource){
        $m=$this->map[$resource]['model']??null; abort_unless($m&&class_exists($m),404);
        $q=$m::query(); $this->applyIncludes($q,$m,$r->get('include'));
        if($s=$r->get('search')){ $cols=$this->map[$resource]['search']??['name']; $q->where(function($w)use($s,$cols){ foreach($cols as $c){ try{$w->orWhere($c,'like',"%{$s}%");}catch(\Throwable $e){} } }); }
        if($f=$r->get('filter')) foreach((array)$f as $k=>$v){ try{ if($v!=='') $q->where($k,$v);}catch(\Throwable $e){} }
        if($r->get('sort')){ try{ $q->orderBy($r->get('sort'),$r->get('dir','desc')); }catch(\Throwable $e){} }
        $p=$q->paginate(min(100,(int)$r->get('per_page',15)));
        if($fields=$r->get('fields')){ $p->getCollection()->transform(fn($row)=>$this->sparse($row,$fields)); }
        return $this->paginated($p,['resource'=>$resource]);
    }
    public function show(Request $r, string $resource, string $id){ $m=$this->map[$resource]['model']??null; abort_unless($m,404); $q=$m::query(); $this->applyIncludes($q,$m,$r->get('include')); return $this->data($this->sparse($q->findOrFail($id),$r->get('fields'))); }
    public function store(Request $r, string $resource){ $m=$this->map[$resource]['model']??null; abort_unless($m,404); $row=$m::create($r->all()); try{ app(\App\Core\Services\WebhookDispatcher::class)->dispatchEvent($resource.'.created',$row->toArray()); }catch(\Throwable $e){} return $this->data($row,['version'=>'v2','created'=>true]); }
    public function update(Request $r, string $resource, string $id){ $m=$this->map[$resource]['model']??null; abort_unless($m,404); $row=$m::findOrFail($id); $row->update($r->all()); return $this->data($row->fresh()); }
    public function destroy(string $resource, string $id){ $m=$this->map[$resource]['model']??null; abort_unless($m,404); $m::findOrFail($id)->delete(); return $this->data(['ok'=>true]); }
}
