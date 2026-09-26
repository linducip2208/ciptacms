<?php
namespace App\Core\Search;
use Illuminate\Support\Facades\Http;
// Meilisearch-ready driver: bicara HTTP API /indexes/{uid}/search.
// Jika host tak terjangkau / index kosong → fallback [] per tipe (tak pernah fatal).
class MeilisearchDriver implements SearchDriverInterface {
    public function name(): string { return 'meilisearch'; }
    public function search(string $query, array $types=[], int $limit=20): array {
        $host=rtrim((string)config('search.meili.host','http://127.0.0.1:7700'),'/');
        $key=(string)config('search.meili.key','');
        $out=[]; $q=trim($query); if($q==='') return $out;
        foreach(($types ?: ['pages','posts','products','leads']) as $t){
            try {
                $res=Http::timeout(3)->withHeaders($key?['Authorization'=>"Bearer {$key}"]:[])->post("{$host}/indexes/lindu_{$t}/search",['q'=>$q,'limit'=>$limit]);
                $out[$t]=$res->successful()?($res->json('hits')??[]):[];
            } catch(\Throwable $e){ $out[$t]=[]; }
        }
        return $out;
    }
}
