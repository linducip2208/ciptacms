<?php
namespace App\Http\Controllers\Api\V1;
use Illuminate\Http\Request;
class ResourceApiController extends ApiController {
    protected array $map=[];
    public function __construct(){ $this->map=config('lindu_admin.resources',[]); }
    public function index(Request $r, string $resource){
        $m=$this->map[$resource]['model']??null; abort_unless($m&&class_exists($m),404);
        $q=$m::query();
        if($s=$r->get('search')){ $cols=$this->map[$resource]['search']??['name']; $q->where(function($w)use($s,$cols){ foreach($cols as $c){ try{$w->orWhere($c,'like',"%{$s}%");}catch(\Throwable $e){} } }); }
        if($r->get('sort')){ try{ $q->orderBy($r->get('sort'),$r->get('dir','desc')); }catch(\Throwable $e){} }
        if($r->get('filter')) foreach((array)$r->get('filter') as $k=>$v){ try{ if($v!=='') $q->where($k,$v);}catch(\Throwable $e){} }
        return $this->paginated($q->paginate(min(100,(int)$r->get('per_page',15))));
    }
    public function show(string $resource, string $id){ $m=$this->map[$resource]['model']??null; abort_unless($m,404); return $this->data($m::findOrFail($id)); }
    public function store(Request $r, string $resource){ $m=$this->map[$resource]['model']??null; abort_unless($m,404); $row=$m::create($r->all()); try{ app(\App\Core\Services\WebhookDispatcher::class)->dispatchEvent($resource.'.created',$row->toArray()); }catch(\Throwable $e){} return $this->data($row); }
    public function update(Request $r, string $resource, string $id){ $m=$this->map[$resource]['model']??null; abort_unless($m,404); $row=$m::findOrFail($id); $row->update($r->all()); return $this->data($row->fresh()); }
    public function destroy(string $resource, string $id){ $m=$this->map[$resource]['model']??null; abort_unless($m,404); $m::findOrFail($id)->delete(); return $this->data(['ok'=>true]); }
}
