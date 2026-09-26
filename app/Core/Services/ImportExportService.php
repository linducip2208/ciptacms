<?php
namespace App\Core\Services;
class ImportExportService {
    public function export(string $model, array $filters=[]): array {
        $rows = $model::query();
        foreach($filters as $k=>$v){ if($v!==null&&$v!=='') $rows->where($k,'like',"%{$v}%"); }
        return $rows->limit(5000)->get()->toArray();
    }
    public function toCsv(array $rows): string {
        if(!$rows) return '';
        $h = fopen('php://temp','r+');
        fputcsv($h, array_keys($rows[0]));
        foreach($rows as $r) fputcsv($h, array_map(fn($v)=>is_array($v)?json_encode($v):$v, array_values($r)));
        rewind($h); $c=stream_get_contents($h); fclose($h); return $c;
    }
    public function parseCsv(string $content): array {
        $lines = array_filter(array_map('trim', explode("\n", trim($content))));
        if(!$lines) return ['headers'=>[],'rows'=>[]];
        $headers = str_getcsv(array_shift($lines));
        $rows=[];
        foreach($lines as $l){ $r=str_getcsv($l); if(count($r)===count($headers)) $rows[]=array_combine($headers,$r); }
        return ['headers'=>$headers,'rows'=>$rows];
    }
}
