<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Aligns the forms tables with what the form builder and the public
 * submission endpoint actually read and write.
 *
 * `forms` shipped with name / submit_text / is_active / settings, but the
 * builder and the brief need a submit label, a success message and a
 * publish state. `form_submissions` had no status column at all, so the
 * public endpoint filtered on a column that did not exist and 404'd every
 * form.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('forms')) {
            Schema::table('forms', function (Blueprint $t) {
                if (! Schema::hasColumn('forms', 'submit_label')) {
                    $t->string('submit_label')->nullable()->after('submit_text');
                }
                if (! Schema::hasColumn('forms', 'success_message')) {
                    $t->text('success_message')->nullable();
                }
                if (! Schema::hasColumn('forms', 'status')) {
                    $t->string('status')->default('published')->index();
                }
                // The Form model uses SoftDeletes; the table never had the column.
                if (! Schema::hasColumn('forms', 'deleted_at')) {
                    $t->softDeletes();
                }
            });

            // Backfill the new state from the pre-existing flag.
            DB::table('forms')->where('is_active', true)->update(['status' => 'published']);
            DB::table('forms')->where('is_active', false)->update(['status' => 'draft']);

            // Anything already saved has no success copy yet.
            DB::table('forms')->whereNull('success_message')->update([
                'success_message' => 'Thank you. Your message has been received.',
            ]);
        }

        if (Schema::hasTable('form_submissions') && ! Schema::hasColumn('form_submissions', 'status')) {
            Schema::table('form_submissions', function (Blueprint $t) {
                $t->string('status')->default('received')->index();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('form_submissions') && Schema::hasColumn('form_submissions', 'status')) {
            Schema::table('form_submissions', function (Blueprint $t) {
                $t->dropColumn('status');
            });
        }

        if (Schema::hasTable('forms')) {
            Schema::table('forms', function (Blueprint $t) {
                $t->dropColumn(['submit_label', 'success_message', 'status']);
                $t->dropSoftDeletes();
            });
        }
    }
};
