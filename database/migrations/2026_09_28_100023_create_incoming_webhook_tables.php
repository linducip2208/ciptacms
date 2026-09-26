<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('webhook_incoming_keys', function (Blueprint $t) {
            $t->id();
            $t->string('tenant_id')->nullable()->index();
            $t->string('name');
            $t->string('key')->unique();
            $t->string('secret')->nullable();
            $t->string('forward_event')->nullable();
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });

        Schema::create('webhook_incoming_logs', function (Blueprint $t) {
            $t->id();
            $t->foreignId('key_id')->constrained('webhook_incoming_keys')->cascadeOnDelete();
            $t->json('payload')->nullable();
            $t->json('headers')->nullable();
            $t->string('method')->default('POST');
            $t->string('ip')->nullable();
            $t->string('status')->default('accepted');
            $t->text('error')->nullable();
            $t->timestamps();
        });

        // webhook_logs needs a place to record HTTP status for retries/UI
        if (Schema::hasTable('webhook_logs') && ! Schema::hasColumn('webhook_logs', 'response_status')) {
            Schema::table('webhook_logs', function (Blueprint $t) {
                $t->integer('response_status')->nullable();
                $t->text('error')->nullable();
                $t->timestamp('delivered_at')->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('webhook_logs') && Schema::hasColumn('webhook_logs', 'response_status')) {
            Schema::table('webhook_logs', function (Blueprint $t) {
                $t->dropColumn(['response_status', 'error', 'delivered_at']);
            });
        }
        Schema::dropIfExists('webhook_incoming_logs');
        Schema::dropIfExists('webhook_incoming_keys');
    }
};
