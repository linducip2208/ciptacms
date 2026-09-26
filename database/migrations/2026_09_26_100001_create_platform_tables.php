<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('modules', function(Blueprint $t){ $t->id(); $t->string('slug')->unique(); $t->string('name'); $t->string('version')->default('1.0.0'); $t->string('author')->nullable(); $t->text('description')->nullable(); $t->json('meta')->nullable(); $t->boolean('is_installed')->default(false); $t->boolean('is_active')->default(false); $t->timestamps(); });
        Schema::create('plugins', function(Blueprint $t){ $t->id(); $t->string('slug')->unique(); $t->string('name'); $t->string('version')->default('1.0.0'); $t->string('author')->nullable(); $t->text('description')->nullable(); $t->json('meta')->nullable(); $t->json('config')->nullable(); $t->boolean('is_installed')->default(false); $t->boolean('is_active')->default(false); $t->timestamps(); });
        Schema::create('themes', function(Blueprint $t){ $t->id(); $t->string('slug')->unique(); $t->string('name'); $t->string('version')->default('1.0.0'); $t->string('author')->nullable(); $t->text('description')->nullable(); $t->json('meta')->nullable(); $t->json('settings')->nullable(); $t->boolean('is_active')->default(false); $t->timestamps(); });
        Schema::create('settings', function(Blueprint $t){ $t->id(); $t->string('tenant_id')->nullable()->index(); $t->string('key')->unique(); $t->text('value')->nullable(); $t->string('type')->default('text'); $t->string('group')->default('general'); $t->boolean('is_public')->default(false); $t->timestamps(); });
        Schema::create('menu_items', function(Blueprint $t){ $t->id(); $t->string('tenant_id')->nullable()->index(); $t->string('location')->default('admin'); $t->foreignId('parent_id')->nullable()->constrained('menu_items')->nullOnDelete(); $t->string('title'); $t->string('icon')->nullable(); $t->string('route')->nullable(); $t->string('url')->nullable(); $t->string('permission')->nullable()->index(); $t->json('roles')->nullable(); $t->string('module')->nullable(); $t->string('badge')->nullable(); $t->string('badge_color')->nullable(); $t->integer('sort_order')->default(0); $t->string('target')->default('_self'); $t->boolean('is_visible')->default(true); $t->json('meta')->nullable(); $t->timestamps(); });
        Schema::create('languages', function(Blueprint $t){ $t->id(); $t->string('code')->unique(); $t->string('name'); $t->boolean('is_active')->default(true); $t->boolean('is_default')->default(false); $t->timestamps(); });
        Schema::create('translations', function(Blueprint $t){ $t->id(); $t->string('locale')->index(); $t->string('group')->default('app'); $t->string('key'); $t->text('value')->nullable(); $t->timestamps(); $t->unique(['locale','group','key']); });
    }
    public function down(): void { foreach(['translations','languages','menu_items','settings','themes','plugins','modules'] as $tb) Schema::dropIfExists($tb); }
};
