<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class FormField extends Model
{
    use HasFactory;

protected $fillable = [
    'form_id',
    'label',
    'name',
    'type',
    'options',
    'validation',
    'conditional',
    'sort_order',
    'is_required',
    'placeholder',
];
protected $casts = [
    'options' => 'array',
    'conditional' => 'array',
    'is_required' => 'boolean',
];


}
