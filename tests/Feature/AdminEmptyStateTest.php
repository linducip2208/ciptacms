<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * An admin table with no rows is a dead end: the operator cannot tell
 * "nothing matches your filter" from "you have not created anything yet".
 *
 * This test finds admin list screens that loop over a collection without
 * providing an empty branch. It is deliberately a source scan rather than a
 * rendered-page assertion, because the failure is the absence of markup and
 * a 200 response says nothing about it.
 */
class AdminEmptyStateTest extends TestCase
{

    /**
     * INVENTORY, not a gate.
     *
     * Records every admin screen that loops over data without an empty
     * branch, so the list can be worked through deliberately rather than
     * discovered one dead-end at a time. Writes storage/admin-empty-state.txt.
     *
     * A table with no rows tells an operator nothing: they cannot tell
     * "nothing matches your filter" from "you have not created anything
     * yet". The shared partial is admin/partials/empty-row.blade.php.
     */
    public function test_admin_empty_state_inventory_is_recorded(): void
    {
        $root = base_path('resources/views/admin');
        $offenders = [];

        foreach (File::allFiles($root) as $file) {
            $relative = ltrim(
                str_replace('\\', '/', substr($file->getPathname(), strlen(str_replace('\\', '/', $root)))),
                '/'
            );

            $source = File::get($file->getPathname());

            if (! preg_match('/@(?:foreach|forelse)\s*\(/', $source)) {
                continue;
            }
            if (preg_match('/@empty\b/', $source)) {
                continue;
            }
            if (str_contains($source, 'admin.partials.empty-row')) {
                continue;
            }

            $offenders[] = $relative;
        }

        sort($offenders);

        $target = base_path('storage/admin-empty-state.txt');
        File::put($target, implode("\n", $offenders)."\n");

        $this->assertStringContainsString(
            'py-5',
            File::get(resource_path('views/admin/partials/empty-row.blade.php')),
            'the shared empty state has no visual weight'
        );
        $this->assertFileExists($target);
    }
}
