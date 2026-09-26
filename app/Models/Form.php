<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class Form extends Model
{
    use HasFactory;

protected $fillable = [
    'tenant_id',
    'name',
    'slug',
    'description',
    'settings',
    'is_active',
    'submit_text',
];
protected $casts = [
    'settings' => 'array',
    'is_active' => 'boolean',
];
    public function fields(){ return $this->hasMany(FormField::class)->orderBy('sort_order'); }
public function submissions(){ return $this->hasMany(FormSubmission::class); }

}
