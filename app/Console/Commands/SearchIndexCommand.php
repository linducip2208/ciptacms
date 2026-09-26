<?php
namespace App\Console\Commands;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
class SearchIndexCommand extends Command {
    protected $signature='lindu:search-index {--driver= : override SEARCH_DRIVER} {--limit=200 : rows per type}';
    protected $description='Push DB content into Meilisearch indexes (index lindu_pages/posts/products/leads). DB driver needs nothing.';
    public function handle(){
        $driver=$this->option('driver') ?: config('search.driver','database');
        if($driver==='database'){ $this->info('Driver database: nothing to index.'); return 0; }
        $host=rtrim((string)config('search.meili.host'),'/'); $key=(string)config('search.meili.key');
        $map=['pages'=>[\App\Models\Page::class,['title','slug','body']],'posts'=>[\App\Models\Post::class,['title','slug','body']],'products'=>[\App\Models\Product::class,['name','sku']],'leads'=>[\App\Models\Lead::class,['name','email']]];
        foreach($map as $idx=>[$model,$cols]){
            try{
                $rows=$model::query()->limit((int)$this->option('limit'))->get()->map(fn($r)=>array_merge(['id'=>$r->id],$r->only($cols)))->values()->all();
                $res=Http::timeout(10)->withHeaders($key?['Authorization'=>"Bearer {$key}"]:[])->post("{$host}/indexes/lindu_{$idx}/documents", $rows);
                $this->info("{$idx}: ".count($rows).' -> '.$res->status());
            }catch(\Throwable $e){ $this->error("{$idx} gagal: ".$e->getMessage()); }
        }
        return 0;
    }
}
