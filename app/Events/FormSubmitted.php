<?php
namespace App\Events;
use Illuminate\Foundation\Events\Dispatchable;
class FormSubmitted { use Dispatchable; public function __construct(public string $formId, public array $data=[]) {} }
