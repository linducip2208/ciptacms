<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Comment extends Model
{
    use HasFactory, SoftDeletes;

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
        'user_agent',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function commentable()
    {
        return $this->morphTo();
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function reports()
    {
        return $this->hasMany(CommentReport::class);
    }

    /** The name shown in the admin, whether the author registered or not. */
    public function getAuthorNameAttribute(): string
    {
        return (string) ($this->guest_name ?: ($this->user?->name ?: 'Guest'));
    }

    public function scopePublished($q)
    {
        return $q->where('status', 'approved');
    }

    public function scopePending($q)
    {
        return $q->where('status', 'pending');
    }
}
