<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class LicenseActivation extends Model
{
    use HasFactory;

protected $fillable = [
    'license_id',
    'domain',
    'ip',
    'activated_at',
];
protected $casts = [
    'activated_at' => 'datetime',
];


}
