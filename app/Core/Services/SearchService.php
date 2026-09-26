<?php
namespace App\Core\Services;
use App\Core\Search\{SearchDriverInterface,DatabaseSearchDriver,MeilisearchDriver};
class SearchService {
    protected function driver(): SearchDriverInterface {
        return match(config('search.driver','database')){
            'meilisearch','meili'=>new MeilisearchDriver(),
            default=>new DatabaseSearchDriver(),
        };
    }
    public function search(string $q, array $types=[], int $limit=20): array {
        try { return $this->driver()->search($q,$types,$limit); }
        catch(\Throwable $e){ return (new DatabaseSearchDriver())->search($q,$types,$limit); }
    }
    public function driverName(): string { return $this->driver()->name(); }
}
