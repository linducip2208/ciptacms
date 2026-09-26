<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('form_fields')) {
            Schema::table('form_fields', function (Blueprint $t) {
                if (! Schema::hasColumn('form_fields', 'is_unique')) {
                    $t->boolean('is_unique')->default(false);
                }
                if (! Schema::hasColumn('form_fields', 'is_active')) {
                    $t->boolean('is_active')->default(true);
                }
                if (! Schema::hasColumn('form_fields', 'help')) {
                    $t->string('help')->nullable();
                }
            });
        }

        if (Schema::hasTable('forms') && ! Schema::hasColumn('forms', 'description')) {
            Schema::table('forms', function (Blueprint $t) {
                $t->text('description')->nullable();
            });
        }

        if (Schema::hasTable('comments') && ! Schema::hasColumn('comments', 'status')) {
            Schema::table('comments', function (Blueprint $t) {
                $t->string('status')->default('pending')->index();
            });
        }

        // The Comment model uses SoftDeletes and the admin trash list relies
        // on it, so the column has to exist.
        if (Schema::hasTable('comments') && ! Schema::hasColumn('comments', 'deleted_at')) {
            Schema::table('comments', function (Blueprint $t) {
                $t->softDeletes();
            });
        }
        if (Schema::hasTable('comments') && ! Schema::hasColumn('comments', 'user_agent')) {
            Schema::table('comments', function (Blueprint $t) {
                $t->string('user_agent')->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('comments') && Schema::hasColumn('comments', 'user_agent')) {
            Schema::table('comments', function (Blueprint $t) {
                $t->dropColumn('user_agent');
            });
        }
        if (Schema::hasTable('comments') && Schema::hasColumn('comments', 'deleted_at')) {
            Schema::table('comments', function (Blueprint $t) {
                $t->dropSoftDeletes();
            });
        }
        if (Schema::hasTable('comments') && Schema::hasColumn('comments', 'status')) {
            Schema::table('comments', function (Blueprint $t) {
                $t->dropColumn('status');
            });
        }
        if (Schema::hasTable('forms') && Schema::hasColumn('forms', 'description')) {
            Schema::table('forms', function (Blueprint $t) {
                $t->dropColumn('description');
            });
        }
        if (Schema::hasTable('form_fields')) {
            Schema::table('form_fields', function (Blueprint $t) {
                $t->dropColumn(['is_unique', 'is_active', 'help']);
            });
        }
    }
};
