<?php
namespace App\Core\Search;
class DatabaseSearchDriver implements SearchDriverInterface {
    public function name(): string { return 'database'; }
    public function search(string $query, array $types=[], int $limit=20): array {
        $out=[]; $q=trim($query); if($q==='') return $out;
        foreach(($types ?: ['pages','posts','products','leads']) as $t){
            try { $out[$t]=$this->{'search'.ucfirst($t)}($q,$limit); } catch(\Throwable $e){ $out[$t]=[]; }
        }
        return $out;
    }
    protected function base($model,$q,$cols,$limit){ return $model::query()->where(function($w)use($q,$cols){ foreach($cols as $c) $w->orWhere($c,'like',"%{$q}%"); })->limit($limit)->get()->toArray(); }
    protected function searchPages($q,$l){ return $this->base(\App\Models\Page::class,$q,['title','slug','body'],$l); }
    protected function searchPosts($q,$l){ return $this->base(\App\Models\Post::class,$q,['title','slug','body'],$l); }
    protected function searchProducts($q,$l){ return class_exists(\App\Models\Product::class)?$this->base(\App\Models\Product::class,$q,['name','sku','description'],$l):[]; }
    protected function searchLeads($q,$l){ return class_exists(\App\Models\Lead::class)?$this->base(\App\Models\Lead::class,$q,['name','email','company'],$l):[]; }
}
