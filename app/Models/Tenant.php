<?php
    namespace App\Models;
    use Illuminate\Database\Eloquent\Factories\HasFactory;
    use Illuminate\Database\Eloquent\Model;
    use Illuminate\Database\Eloquent\SoftDeletes;
    class Tenant extends Model
    {
        use HasFactory;
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'uuid',
        'name',
        'slug',
        'domain',
        'subdomain',
        'status',
        'plan_id',
        'settings',
        'quotas',
        'trial_ends_at',
        'expires_at',
    ];
    protected $casts = [
        'settings' => 'array',
        'quotas' => 'array',
        'trial_ends_at' => 'datetime',
        'expires_at' => 'datetime',
    ];
    public function users(){ return $this->belongsToMany(User::class,'tenant_user')->withPivot('role_id'); }
public function domains(){ return $this->hasMany(TenantDomain::class); }
public function plan(){ return $this->belongsTo(Plan::class); }
public function subscriptions(){ return $this->hasMany(Subscription::class); }


    }
