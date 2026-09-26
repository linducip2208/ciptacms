<?php
namespace App\Core\Search;
interface SearchDriverInterface {
    public function search(string $query, array $types=[], int $limit=20): array;
    public function name(): string;
}
