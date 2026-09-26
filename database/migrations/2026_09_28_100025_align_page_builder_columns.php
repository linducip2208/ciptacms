<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Aligns the page-builder tables with what the builder UI actually stores.
 *
 * The original migration gave reusable_blocks a `blocks` column and no
 * activation flag, and page_templates a `blocks` column with no description
 * or active flag. The builder writes `data` and `structure`, so without this
 * every insert silently discarded the layout.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('reusable_blocks')) {
            Schema::table('reusable_blocks', function (Blueprint $t) {
                if (! Schema::hasColumn('reusable_blocks', 'data')) {
                    $t->json('data')->nullable()->after('slug');
                }
                if (! Schema::hasColumn('reusable_blocks', 'is_active')) {
                    $t->boolean('is_active')->default(true)->after('is_global');
                }
                if (! Schema::hasColumn('reusable_blocks', 'deleted_at')) {
                    $t->softDeletes();
                }
            });

            // Backfill data from the legacy blocks column.
            DB::table('reusable_blocks')->whereNull('data')->update([
                'data' => DB::raw('blocks'),
            ]);
        }

        if (Schema::hasTable('page_templates')) {
            Schema::table('page_templates', function (Blueprint $t) {
                if (! Schema::hasColumn('page_templates', 'structure')) {
                    $t->json('structure')->nullable()->after('slug');
                }
                if (! Schema::hasColumn('page_templates', 'description')) {
                    $t->string('description')->nullable();
                }
                if (! Schema::hasColumn('page_templates', 'thumbnail')) {
                    $t->string('thumbnail')->nullable()->after('screenshot');
                }
                if (! Schema::hasColumn('page_templates', 'is_active')) {
                    $t->boolean('is_active')->default(true);
                }
                if (! Schema::hasColumn('page_templates', 'deleted_at')) {
                    $t->softDeletes();
                }
            });

            DB::table('page_templates')->whereNull('structure')->update([
                'structure' => DB::raw('blocks'),
            ]);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('page_templates')) {
            Schema::table('page_templates', function (Blueprint $t) {
                $t->dropColumn(['structure', 'description', 'thumbnail', 'is_active']);
                $t->dropSoftDeletes();
            });
        }

        if (Schema::hasTable('reusable_blocks')) {
            Schema::table('reusable_blocks', function (Blueprint $t) {
                $t->dropColumn(['data', 'is_active']);
                $t->dropSoftDeletes();
            });
        }
    }
};
