<?php
    namespace App\Models;
    use Illuminate\Database\Eloquent\Factories\HasFactory;
    use Illuminate\Database\Eloquent\Model;
    use Illuminate\Database\Eloquent\SoftDeletes;
    class Permission extends Model
    {
        use HasFactory;

    protected $fillable = [
        'group_id',
        'tenant_id',
        'name',
        'slug',
        'action',
        'module',
        'description',
    ];
    public function roles(){ return $this->belongsToMany(Role::class,'permission_role'); }
public function group(){ return $this->belongsTo(PermissionGroup::class,'group_id'); }


    }
