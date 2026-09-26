<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::table('media_files', function(Blueprint $t){
            if(!Schema::hasColumn('media_files','variants')) $t->json('variants')->nullable();
            if(!Schema::hasColumn('media_files','optimized')) $t->boolean('optimized')->default(false);
            if(!Schema::hasColumn('media_files','status')) $t->string('status')->default('ready');
        });
        Schema::table('users', function(Blueprint $t){
            if(!Schema::hasColumn('users','two_factor_enabled')) $t->boolean('two_factor_enabled')->default(false);
            if(!Schema::hasColumn('users','two_factor_backup_codes')) $t->json('two_factor_backup_codes')->nullable();
        });
        Schema::table('sessions', function(Blueprint $t){
            if(!Schema::hasColumn('sessions','device')) $t->string('device')->nullable();
        });
    }
    public function down(): void {}
};
