<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CommentReport extends Model
{
    protected $table = 'comment_reports';

    protected $fillable = ['comment_id', 'reporter_ip', 'reason', 'status'];

    protected $casts = ['status' => 'string'];

    public function comment()
    {
        return $this->belongsTo(Comment::class);
    }

    public function scopeOpen($q)
    {
        return $q->where('status', 'open');
    }
}
