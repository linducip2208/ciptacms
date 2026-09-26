<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class Room extends Model
{
    use HasFactory;

protected $fillable = [
    'tenant_id',
    'property_id',
    'room_type_id',
    'number',
    'floor',
    'status',
    'notes',
];
    public function type(){ return $this->belongsTo(RoomType::class,'room_type_id'); }

}
