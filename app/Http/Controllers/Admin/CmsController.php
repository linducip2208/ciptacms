<?php
namespace App\Http\Controllers\Admin;
use Illuminate\Http\Request; use App\Models\{Page,PageRevision,Post,Category,Tag,Comment,SeoMeta,Form,FormField,FormSubmission,ContentType,ContentRecord,Workflow,Webhook,NotificationTemplate,PageTemplate,ReusableBlock};
class CmsController extends AdminController {
    public function pages(Request $r){ $q=Page::latest(); if($s=$r->get('search')) $q->where('title','like',"%{$s}%"); if($st=$r->get('status')) $q->where('status',$st); return view('admin.cms.pages',['rows'=>$q->paginate(20)]); }
    public function pageForm(?Page $page=null){ return view('admin.cms.page-builder',['row'=>$page??new Page(),'templates'=>PageTemplate::all(),'blocks'=>ReusableBlock::all()]); }
    public function pageSave(Request $r, ?string $id=null){
        $d=$r->validate(['title'=>'required','slug'=>'nullable','body'=>'nullable','status'=>'nullable']);
        if(empty($d['slug'])) $d['slug']=\Illuminate\Support\Str::slug($d['title']).'-'.\Illuminate\Support\Str::lower(\Illuminate\Support\Str::random(4));
        $d['builder']=$r->get('builder')?json_decode($r->get('builder'),true):$r->get('builder_array');
        $row=$id?Page::findOrFail($id):new Page();
        if($row->exists) PageRevision::create(['page_id'=>$row->id,'user_id'=>$r->user()?->id,'data'=>$row->toArray()]);
        $row->fill($d); $row->author_id=$row->author_id??$r->user()?->id; $row->save();
        if($r->filled('meta_title')) SeoMeta::updateOrCreate(['seoable_type'=>Page::class,'seoable_id'=>$row->id],$r->only(['meta_title','meta_description','canonical','robots','og_title','og_description','og_image']));
        return redirect()->route('admin.cms.pages')->with('ok','Saved');
    }
    public function posts(Request $r){ $q=Post::with(['category','author'])->latest(); if($s=$r->get('search')) $q->where('title','like',"%{$s}%"); return view('admin.cms.posts',['rows'=>$q->paginate(20),'categories'=>Category::all()]); }
    public function comments(Request $r){ $q=Comment::latest(); if($st=$r->get('status')) $q->where('status',$st); return view('admin.cms.comments',['rows'=>$q->paginate(20)]); }
    public function moderate(Comment $comment, string $status){ $comment->update(['status'=>$status]); return back()->with('ok','Moderated'); }
    public function forms(){ return view('admin.cms.forms',['rows'=>Form::withCount(['fields','submissions'])->get()]); }
    public function formBuilder(Form $form){ return view('admin.cms.form-builder',['form'=>$form->load(['fields','submissions'])]); }
    public function contentTypes(){ return view('admin.cms.content-types',['rows'=>ContentType::withCount('records')->get()]); }
    public function contentTypeSave(Request $r, ?string $id=null){ $d=$r->validate(['name'=>'required','slug'=>'required']); $d['fields']=$r->get('fields')?json_decode($r->get('fields'),true):[]; $row=$id?ContentType::findOrFail($id):new ContentType(); $row->fill($d+['is_api_enabled'=>true])->save(); try{ app(\App\Core\Services\DataBuilderService::class)->createPhysicalTable($row->fresh()); }catch(\Throwable $e){} return back()->with('ok','Content type saved'); }
    public function workflows(){ return view('admin.cms.workflows',['rows'=>Workflow::withCount('runs')->get()]); }
    public function workflowSave(Request $r){ Workflow::create($r->validate(['name'=>'required','trigger_event'=>'required'])+['conditions'=>$r->get('conditions',[]),'actions'=>$r->get('actions',[]),'is_active'=>true]); return back()->with('ok','Workflow saved'); }
    public function webhooks(){ return view('admin.cms.webhooks',['rows'=>Webhook::withCount('logs')->get()]); }
    public function seo(){ return view('admin.cms.seo',['rows'=>SeoMeta::latest()->paginate(20)]); }
}
