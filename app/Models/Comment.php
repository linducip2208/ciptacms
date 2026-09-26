<?php
    namespace App\Models;
    use Illuminate\Database\Eloquent\Factories\HasFactory;
    use Illuminate\Database\Eloquent\Model;
    use Illuminate\Database\Eloquent\SoftDeletes;
    class Comment extends Model
    {
        use HasFactory;

    protected $fillable = [
        'tenant_id',
        'commentable_type',
        'commentable_id',
        'user_id',
        'guest_name',
        'guest_email',
        'body',
        'status',
        'ip',
    ];
    public function commentable(){ return $this->morphTo(); }
public function user(){ return $this->belongsTo(User::class); }


    }
