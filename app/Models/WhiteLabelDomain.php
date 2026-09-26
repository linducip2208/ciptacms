<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhiteLabelDomain extends Model
{
    protected $table = 'white_label_domains';

    protected $fillable = ['tenant_id', 'domain', 'is_primary', 'is_verified'];

    protected $casts = [
        'is_primary' => 'boolean',
        'is_verified' => 'boolean',
    ];
}
