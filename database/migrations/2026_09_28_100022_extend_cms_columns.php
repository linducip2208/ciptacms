<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('pages') && ! Schema::hasColumn('pages', 'is_homepage')) {
            Schema::table('pages', function (Blueprint $t) {
                $t->boolean('is_homepage')->default(false)->after('template');
                $t->unsignedBigInteger('views')->default(0);
                $t->text('meta_description')->nullable();
            });
        }

        if (Schema::hasTable('posts') && ! Schema::hasColumn('posts', 'meta_description')) {
            Schema::table('posts', function (Blueprint $t) {
                $t->text('meta_description')->nullable();
            });
        }

        // Menu items need a stable key for reordering / module registration
        if (Schema::hasTable('menu_items') && ! Schema::hasColumn('menu_items', 'uuid')) {
            Schema::table('menu_items', function (Blueprint $t) {
                $t->uuid('uuid')->nullable()->unique();
            });
        }

        // Media: caption/title/description used by the library UI
        if (Schema::hasTable('media_files') && ! Schema::hasColumn('media_files', 'title')) {
            Schema::table('media_files', function (Blueprint $t) {
                $t->string('title')->nullable();
                $t->text('caption')->nullable();
                $t->text('description')->nullable();
                $t->string('folder_name')->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('media_files') && Schema::hasColumn('media_files', 'title')) {
            Schema::table('media_files', function (Blueprint $t) {
                $t->dropColumn(['title', 'caption', 'description', 'folder_name']);
            });
        }
        if (Schema::hasTable('menu_items') && Schema::hasColumn('menu_items', 'uuid')) {
            Schema::table('menu_items', function (Blueprint $t) {
                $t->dropColumn('uuid');
            });
        }
        if (Schema::hasTable('posts') && Schema::hasColumn('posts', 'meta_description')) {
            Schema::table('posts', function (Blueprint $t) {
                $t->dropColumn('meta_description');
            });
        }
        if (Schema::hasTable('pages') && Schema::hasColumn('pages', 'is_homepage')) {
            Schema::table('pages', function (Blueprint $t) {
                $t->dropColumn(['is_homepage', 'views', 'meta_description']);
            });
        }
    }
};
