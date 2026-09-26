<?php
namespace App\Http\Controllers\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
class ResourceController extends AdminController {
    protected array $map = [];
    public function __construct(){ $this->map = config('lindu_admin.resources', []); }
    protected function modelFor(string $resource){ $m = $this->map[$resource]['model'] ?? null; abort_unless($m && class_exists($m), 404); return $m; }
    public function index(Request $r, string $resource){
        $model = $this->modelFor($resource);
        $q = $model::query();
        if ($s=$r->get('search')) { $cols = $this->map[$resource]['search'] ?? ['name','title']; $q->where(function($w)use($s,$cols){ foreach($cols as $c){ try{$w->orWhere($c,'like',"%{$s}%");}catch(\Throwable $e){} } }); }
        foreach((array)$r->get('filter',[]) as $k=>$v){ if($v!==null&&$v!==''&&Schema::hasColumn((new $model)->getTable(),$k)) $q->where($k,$v); }
        $sort = $r->get('sort','id'); $dir = $r->get('dir','desc');
        try{ $q->orderBy($sort,$dir);}catch(\Throwable $e){}
        $rows = $q->paginate((int)$r->get('per_page',15))->withQueryString();
        if($r->expectsJson()) return $this->ok($rows);
        $first=$rows->first()?->toArray() ?? ['name'=>''];
        $columns=array_slice(array_keys($first),0,5);
        return view('admin.resource.index',['resource'=>$resource,'rows'=>$rows,'config'=>$this->map[$resource],'columns'=>$columns]);
    }
    public function create(string $resource){ return view('admin.resource.form',['resource'=>$resource,'row'=>null,'config'=>$this->map[$resource]]); }
    public function store(Request $r, string $resource){
        $model=$this->modelFor($resource);
        $data=$r->except(['_token']);
        $row=$model::create($data);
        $this->audit('create',$row,$r);
        if($r->expectsJson()) return $this->ok($row,'Created');
        return redirect()->route('admin.resource.index',$resource)->with('ok','Created');
    }
    public function edit(string $resource, string $id){ $model=$this->modelFor($resource); $row=$model::findOrFail($id); return view('admin.resource.form',['resource'=>$resource,'row'=>$row,'config'=>$this->map[$resource]]); }
    public function update(Request $r, string $resource, string $id){
        $model=$this->modelFor($resource); $row=$model::findOrFail($id);
        $old=$row->getAttributes(); $row->update($r->except(['_token','_method']));
        try{ app(\App\Core\Services\AuditService::class)->log('update',$row,['old'=>$old]); }catch(\Throwable $e){}
        if($r->expectsJson()) return $this->ok($row->fresh(),'Updated');
        return redirect()->route('admin.resource.index',$resource)->with('ok','Updated');
    }
    public function destroy(Request $r, string $resource, string $id){
        $model=$this->modelFor($resource); $row=$model::findOrFail($id); $row->delete();
        $this->audit('delete',$row,$r);
        if($r->expectsJson()) return $this->ok([],'Deleted');
        return back()->with('ok','Deleted');
    }
    public function bulk(Request $r, string $resource){
        $model=$this->modelFor($resource); $ids=(array)$r->get('ids',[]); $action=$r->get('action','delete');
        if($action==='delete') $model::whereIn('id',$ids)->delete();
        return $this->ok(['affected'=>count($ids)]);
    }
    public function export(Request $r, string $resource){
        $model=$this->modelFor($resource);
        $rows=app(\App\Core\Services\ImportExportService::class)->export($model,(array)$r->get('filter',[]));
        $csv=app(\App\Core\Services\ImportExportService::class)->toCsv($rows);
        return response($csv,200,['Content-Type'=>'text/csv','Content-Disposition'=>"attachment; filename={$resource}.csv"]);
    }
}
