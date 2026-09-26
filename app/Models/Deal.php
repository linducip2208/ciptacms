<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class Deal extends Model
{
    use HasFactory;

protected $fillable = [
    'tenant_id',
    'pipeline_id',
    'stage',
    'title',
    'value',
    'currency',
    'company_id',
    'contact_id',
    'expected_close',
    'status',
];
protected $casts = [
    'expected_close' => 'date',
];


}
