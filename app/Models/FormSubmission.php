<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class FormSubmission extends Model
{
    use HasFactory;

protected $fillable = [
    'form_id',
    'tenant_id',
    'data',
    'ip',
    'user_agent',
];
protected $casts = [
    'data' => 'array',
];


}
