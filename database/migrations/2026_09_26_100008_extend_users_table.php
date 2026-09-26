<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::table('users', function(Blueprint $t){
            if(!Schema::hasColumn('users','tenant_id')) $t->string('tenant_id')->nullable()->index()->after('id');
            if(!Schema::hasColumn('users','username')) $t->string('username')->nullable()->unique()->after('name');
            if(!Schema::hasColumn('users','phone')) $t->string('phone')->nullable()->after('email');
            if(!Schema::hasColumn('users','avatar')) $t->string('avatar')->nullable();
            if(!Schema::hasColumn('users','status')) $t->string('status')->default('active');
            if(!Schema::hasColumn('users','is_active')) $t->boolean('is_active')->default(true);
            if(!Schema::hasColumn('users','last_login_at')) $t->timestamp('last_login_at')->nullable();
            if(!Schema::hasColumn('users','last_login_ip')) $t->string('last_login_ip')->nullable();
            if(!Schema::hasColumn('users','two_factor_secret')) $t->text('two_factor_secret')->nullable();
            if(!Schema::hasColumn('users','preferences')) $t->json('preferences')->nullable();
            if(!Schema::hasColumn('users','locale')) $t->string('locale')->default('id');
            if(!Schema::hasColumn('users','timezone')) $t->string('timezone')->default('Asia/Jakarta');
        });
    }
    public function down(): void {}
};
