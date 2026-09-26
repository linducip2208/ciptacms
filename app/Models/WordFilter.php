<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WordFilter extends Model
{
    protected $table = 'word_filters';

    protected $fillable = ['tenant_id', 'word', 'action', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function scopeActive($q)
    {
        return $q->where('is_active', true);
    }
}
