<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('social_accounts', function(Blueprint $t){
            $t->id(); $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->string('provider'); $t->string('provider_id'); $t->string('email')->nullable();
            $t->json('meta')->nullable(); $t->timestamps();
            $t->unique(['provider','provider_id']);
        });
    }
    public function down(): void { Schema::dropIfExists('social_accounts'); }
};
