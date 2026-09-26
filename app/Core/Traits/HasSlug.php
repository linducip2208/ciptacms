<?php
namespace App\Core\Traits;
use Illuminate\Support\Str;
trait HasSlug {
    public static function bootHasSlug(): void {
        static::creating(function ($m) {
            if (empty($m->slug) && isset($m->title)) $m->slug = Str::slug($m->title).'-'.Str::lower(Str::random(4));
            elseif (empty($m->slug) && isset($m->name)) $m->slug = Str::slug($m->name).'-'.Str::lower(Str::random(4));
        });
    }
}
