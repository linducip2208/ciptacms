<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('permission_groups', function(Blueprint $t){ $t->id(); $t->string('name'); $t->string('slug')->unique(); $t->text('description')->nullable(); $t->timestamps(); });
        Schema::create('permissions', function(Blueprint $t){ $t->id(); $t->foreignId('group_id')->nullable()->constrained('permission_groups')->nullOnDelete(); $t->string('tenant_id')->nullable()->index(); $t->string('name'); $t->string('slug')->unique(); $t->string('action')->default('view'); $t->string('module')->nullable(); $t->text('description')->nullable(); $t->timestamps(); });
        Schema::create('roles', function(Blueprint $t){ $t->id(); $t->string('tenant_id')->nullable()->index(); $t->string('name'); $t->string('slug')->unique(); $t->text('description')->nullable(); $t->boolean('is_system')->default(false); $t->integer('level')->default(0); $t->timestamps(); });
        Schema::create('role_user', function(Blueprint $t){ $t->id(); $t->foreignId('role_id')->constrained()->cascadeOnDelete(); $t->foreignId('user_id')->constrained()->cascadeOnDelete(); $t->unique(['role_id','user_id']); });
        Schema::create('permission_role', function(Blueprint $t){ $t->id(); $t->foreignId('permission_id')->constrained()->cascadeOnDelete(); $t->foreignId('role_id')->constrained()->cascadeOnDelete(); $t->unique(['permission_id','role_id']); });
    }
    public function down(): void { Schema::dropIfExists('permission_role'); Schema::dropIfExists('role_user'); Schema::dropIfExists('roles'); Schema::dropIfExists('permissions'); Schema::dropIfExists('permission_groups'); }
};
