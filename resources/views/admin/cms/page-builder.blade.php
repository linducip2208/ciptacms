@extends('admin.layout')@section('title','Page builder')@section('crumb','Pages / Builder')
@section('content')
<div class="card max-w-6xl" x-data="builder()" x-init="init()">
<div class="grid md:grid-cols-2 gap-3 mb-3">
<div><label class="form-label">Title</label><input name="title" form="pageForm" value="{{ old('title',$row->title) }}" required class="form-control"></div>
<div><label class="form-label">Slug</label><input name="slug" form="pageForm" value="{{ old('slug',$row->slug) }}" class="form-control"></div>
</div>
<div class="d-flex flex-wrap gap-2 mb-3 items-center">
<template x-for="b in palette" :key="b.type"><button type="button" @click="addBlock(b.type)" class="border rounded-lg px-2 py-1 text-xs hover:bg-indigo-50" x-text="b.label"></button></template>
<span class="flex-1"></span>
<template x-for="d in ['desktop','tablet','mobile']"><button type="button" @click="device=d" :class="device===d?'btn-primary':'border rounded-lg px-2 py-1 text-xs'" x-text="d"></button></template>
<button type="button" @click="undo()" class="border rounded-lg px-2 py-1 text-xs">Undo</button>
<button type="button" @click="redo()" class="border rounded-lg px-2 py-1 text-xs">Redo</button>
<button type="button" @click="preview=!preview" class="border rounded-lg px-2 py-1 text-xs">Preview</button>
</div>
<div class="row row-cards">
<div>
<h4 class="text-sm font-semibold mb-1">Sections (<span x-text="sections.length"></span>)</h4>
<template x-for="(s,si) in sections" :key="s._id">
<div class="border rounded-lg p-2 mb-2 bg-slate-50 dark:bg-slate-800">
<div class="flex gap-1 items-center mb-1">
<input x-model="s.name" class="form-control !w-32 !py-1 !text-xs" placeholder="Section">
<select x-model="s.layout" class="form-control !w-28 !py-1 !text-xs"><option>1-col</option><option>2-col</option><option>3-col</option><option>hero</option></select>
<select x-model="s.visibility[device]" class="form-control !w-28 !py-1 !text-xs"><option value="show">show</option><option value="hide">hide</option></select>
<button @click="move(sections,si,-1)" class="text-xs">↑</button><button @click="move(sections,si,1)" class="text-xs">↓</button>
<button @click="removeSection(si)" class="text-xs text-rose-600">✕</button>
</div>
<template x-for="(b,bi) in s.blocks" :key="b._id">
<div class="bg-white dark:bg-slate-900 border rounded p-2 mb-1">
<div class="flex gap-1 items-center"><b class="text-xs" x-text="b.type"></b><span class="flex-1"></span>
<button @click="move(s.blocks,bi,-1)" class="text-xs">↑</button><button @click="move(s.blocks,bi,1)" class="text-xs">↓</button>
<button @click="s.blocks.splice(bi,1);push()" class="text-xs text-rose-600">✕</button></div>
<input x-model="b.heading" @input="push()" class="form-control !py-1 !text-xs mt-1" placeholder="Heading">
<textarea x-model="b.text" @input="push()" class="form-control !py-1 !text-xs mt-1" rows="2" placeholder="Text / HTML"></textarea>
<div class="row g-1 mt-1"><input x-model="b.image" @input="push()" class="form-control !py-1 !text-xs" placeholder="Image URL"><input x-model="b.link" @input="push()" class="form-control !py-1 !text-xs" placeholder="Button link"></div>
</div>
</template>
<button @click="addToSection(si)" class="text-xs text-indigo-600">+ block ke section ini</button>
</div>
</template>
<button @click="addSection()" class="btn btn-primary text-xs">+ Section</button>
@if($templates->count())<div class="mt-2 text-xs">Template: <template x-for="t in tmpl" :key="t.slug"><button type="button" @click="loadBlocks(t.blocks)" class="badge ml-1" x-text="t.name"></button></template></div>@endif
</div>
<div>
<h4 class="text-sm font-semibold mb-1">Live preview (<span x-text="device"></span>)</h4>
<div class="border rounded-lg p-4 bg-white dark:bg-slate-900 min-h-[300px]" :class="device==='mobile'?'max-w-[360px] mx-auto':device==='tablet'?'max-w-[640px] mx-auto':''">
<template x-for="s in sections" :key="s._id"><div x-show="s.visibility[device]==='show'" class="mb-4 border-b pb-3">
<template x-for="b in s.blocks" :key="b._id"><div class="mb-2">
<div x-if="b.type==='heading'" class="text-xl font-bold" x-text="b.heading||'(heading)'"></div>
<div x-if="b.type==='text'" class="text-sm" x-text="b.text||'(text)'"></div>
<div x-if="b.type==='image'"><div class="bg-slate-100 rounded h-24 grid place-items-center text-xs" x-text="b.image||'(image)'"></div></div>
<div x-if="b.type==='button'"><span class="btn btn-primary text-xs" x-text="b.heading||'Button'"></span></div>
<div x-if="!['heading','text','image','button'].includes(b.type)" class="text-xs border rounded p-2"><b x-text="b.type"></b>: <span x-text="b.heading"></span> <span x-text="b.text"></span></div>
</div></template>
</div></template>
<div x-show="!sections.length" class="text-slate-400 text-sm">Kosong — tambah section & block.</div>
</div>
</div>
</div>
<form id="pageForm" method="POST" action="{{ route('admin.cms.pages.save',$row->id) }}">@csrf
<input type="hidden" name="builder" :value="JSON.stringify({sections:sections})">
<div class="grid md:grid-cols-3 gap-2 mt-3">
<select name="status" class="form-control"><option value="draft" {{ $row->status=='draft'?'selected':'' }}>draft</option><option value="published" {{ $row->status=='published'?'selected':'' }}>published</option><option value="scheduled" {{ $row->status=='scheduled'?'selected':'' }}>scheduled</option></select>
<input name="template" value="{{ $row->template }}" class="form-control" placeholder="template">
<input name="featured_image" value="{{ $row->featured_image }}" class="form-control" placeholder="featured image">
</div>
<textarea name="body" rows="4" class="form-control font-mono mt-2" placeholder="Body HTML (opsional)">{{ old('body',$row->body) }}</textarea>
<button class="btn btn-primary mt-3">Save page + builder</button>
</form>
</div>
<script>
function builder(){
return {
device:'desktop', sections:[], history:[], hIndex:-1, preview:false,
palette:[{type:'heading',label:'Heading'},{type:'text',label:'Text'},{type:'image',label:'Image'},{type:'video',label:'Video'},{type:'button',label:'Button'},{type:'icon',label:'Icon'},{type:'card',label:'Card'},{type:'grid',label:'Grid'},{type:'gallery',label:'Gallery'},{type:'slider',label:'Slider'},{type:'tabs',label:'Tabs'},{type:'accordion',label:'Accordion'},{type:'testimonials',label:'Testimonials'},{type:'pricing',label:'Pricing'},{type:'team',label:'Team'},{type:'contact',label:'Contact'},{type:'map',label:'Map'},{type:'form',label:'Form'},{type:'html',label:'HTML'},{type:'code',label:'Code'},{type:'dynamic',label:'Dynamic'}],
tmpl: @json($templates->map(fn($t)=>['name'=>$t->name,'slug'=>$t->slug,'blocks'=>$t->blocks])),
init(){ try{ const raw=@json($row->builder ?? ['sections'=>[]]); this.sections=(raw.sections||[]).map(s=>({...s,_id:s._id||Math.random().toString(36).slice(2),visibility:s.visibility||{desktop:'show',tablet:'show',mobile:'show'},blocks:(s.blocks||[]).map(b=>({...b,_id:b._id||Math.random().toString(36).slice(2)}))})); }catch(e){ this.sections=[]; } this.push(true); },
mkBlock(t){ return {type:t,_id:Math.random().toString(36).slice(2),heading:'',text:'',image:'',link:''}; },
addBlock(t){ if(!this.sections.length) this.addSection(true); this.sections[this.sections.length-1].blocks.push(this.mkBlock(t)); this.push(); },
addToSection(si){ const t=prompt('Block type (heading/text/image/video/button/card/...)','text')||'text'; this.sections[si].blocks.push(this.mkBlock(t)); this.push(); },
addSection(silent){ this.sections.push({_id:Math.random().toString(36).slice(2),name:'Section '+(this.sections.length+1),layout:'1-col',visibility:{desktop:'show',tablet:'show',mobile:'show'},blocks:[this.mkBlock('heading')]}); if(!silent) this.push(); else this.push(true); },
removeSection(i){ this.sections.splice(i,1); this.push(); },
move(a,i,d){ const j=i+d; if(j<0||j>=a.length) return; const [x]=a.splice(i,1); a.splice(j,0,x); this.push(); },
loadBlocks(b){ this.addSection(true); try{ const arr=Array.isArray(b)?b:(b.sections||[]); if(arr.length&&arr[0].blocks){ this.sections[this.sections.length-1].blocks=arr[0].blocks.map(x=>({...x,_id:Math.random().toString(36).slice(2)})); } }catch(e){} this.push(); },
push(reset){ const snap=JSON.stringify(this.sections); if(reset){ this.history=[snap]; this.hIndex=0; return; } this.history=this.history.slice(0,this.hIndex+1); this.history.push(snap); if(this.history.length>50) this.history.shift(); this.hIndex=this.history.length-1; },
undo(){ if(this.hIndex>0){ this.hIndex--; this.sections=JSON.parse(this.history[this.hIndex]); } },
redo(){ if(this.hIndex<this.history.length-1){ this.hIndex++; this.sections=JSON.parse(this.history[this.hIndex]); } },
}}
</script>
@endsection
