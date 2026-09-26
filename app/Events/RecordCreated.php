<?php
namespace App\Events;
use Illuminate\Foundation\Events\Dispatchable;
class RecordCreated { use Dispatchable; public function __construct(public string $model, public $id, public array $payload=[]) {} }
