<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FormSpamSetting extends Model
{
    protected $table = 'form_spam_settings';

    protected $fillable = [
        'tenant_id', 'honeypot', 'rate_limit_per_minute', 'min_fill_seconds',
        'block_disposable_email', 'captcha', 'blocked_words',
    ];

    protected $casts = [
        'honeypot' => 'boolean',
        'block_disposable_email' => 'boolean',
        'captcha' => 'boolean',
        'blocked_words' => 'array',
        'rate_limit_per_minute' => 'integer',
        'min_fill_seconds' => 'integer',
    ];

    public static function current(): self
    {
        $row = static::query()->latest('id')->first();
        if ($row) {
            return $row;
        }

        return new static(['honeypot' => true, 'rate_limit_per_minute' => 5, 'min_fill_seconds' => 2]);
    }
}
