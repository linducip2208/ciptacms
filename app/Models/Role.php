<?php
    namespace App\Models;
    use Illuminate\Database\Eloquent\Factories\HasFactory;
    use Illuminate\Database\Eloquent\Model;
    use Illuminate\Database\Eloquent\SoftDeletes;
    class Role extends Model
    {
        use HasFactory;

    protected $fillable = [
        'tenant_id',
        'name',
        'slug',
        'description',
        'is_system',
        'level',
    ];
    protected $casts = [
        'is_system' => 'boolean',
    ];
    public function users(){ return $this->belongsToMany(User::class,'role_user'); }
public function permissions(){ return $this->belongsToMany(Permission::class,'permission_role'); }


    }
