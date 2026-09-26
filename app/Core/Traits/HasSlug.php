<?php

namespace App\Core\Traits;

use Illuminate\Support\Str;

trait HasSlug
{
    /**
     * Attributes checked, in order, when generating a slug. Models with a
     * different source column should add a static slugSource() override.
     */
    protected static function slugCandidates(): array
    {
        return ['title', 'name', 'position', 'question', 'customer', 'label'];
    }

    public static function bootHasSlug(): void
    {
        static::creating(function ($m) {
            if (! empty($m->slug)) {
                return;
            }

            $source = null;
            foreach (static::slugCandidates() as $attr) {
                if (! empty($m->{$attr})) {
                    $source = (string) $m->{$attr};
                    break;
                }
            }

            if ($source === null) {
                return;
            }

            $base = Str::slug($source);
            if ($base === '') {
                $base = 'item';
            }

            $m->slug = $base.'-'.Str::lower(Str::random(4));
        });
    }
}
