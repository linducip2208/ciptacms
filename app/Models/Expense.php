<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class Expense extends Model
{
    use HasFactory;

protected $fillable = [
    'tenant_id',
    'category',
    'amount',
    'currency',
    'description',
    'spent_at',
];
protected $casts = [
    'spent_at' => 'date',
];


}
