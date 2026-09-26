<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // SEO redirects
        Schema::create('seo_redirects', function (Blueprint $t) {
            $t->id();
            $t->string('tenant_id')->nullable()->index();
            $t->string('from_path');
            $t->string('to_path');
            $t->integer('status_code')->default(301);
            $t->integer('hits')->default(0);
            $t->boolean('is_active')->default(true);
            $t->timestamps();
            $t->unique('from_path');
        });

        // Comment moderation extras
        Schema::create('comment_reports', function (Blueprint $t) {
            $t->id();
            $t->foreignId('comment_id')->nullable()->constrained('comments')->cascadeOnDelete();
            $t->string('reporter_ip')->nullable();
            $t->string('reason')->nullable();
            $t->string('status')->default('open');
            $t->timestamps();
        });

        Schema::create('word_filters', function (Blueprint $t) {
            $t->id();
            $t->string('tenant_id')->nullable()->index();
            $t->string('word');
            $t->string('action')->default('spam');
            $t->boolean('is_active')->default(true);
            $t->timestamps();
            $t->unique('word');
        });

        // Activity log (distinct from audit log which records entity diffs)
        Schema::create('activity_logs', function (Blueprint $t) {
            $t->id();
            $t->string('tenant_id')->nullable()->index();
            $t->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $t->string('channel')->default('web');
            $t->string('action');
            $t->string('description')->nullable();
            $t->string('subject_type')->nullable();
            $t->string('subject_id')->nullable();
            $t->json('properties')->nullable();
            $t->string('ip')->nullable();
            $t->timestamps();
            $t->index(['action', 'created_at']);
        });

        // Notification delivery log
        Schema::create('notification_deliveries', function (Blueprint $t) {
            $t->id();
            $t->string('tenant_id')->nullable()->index();
            $t->foreignId('notification_template_id')->nullable()->constrained('notification_templates')->nullOnDelete();
            $t->string('channel');
            $t->string('recipient');
            $t->string('subject')->nullable();
            $t->text('body')->nullable();
            $t->string('status')->default('pending');
            $t->text('error')->nullable();
            $t->unsignedInteger('attempts')->default(0);
            $t->timestamp('sent_at')->nullable();
            $t->timestamps();
        });

        // SaaS: feature flags & usage metering
        Schema::create('plan_features', function (Blueprint $t) {
            $t->id();
            $t->foreignId('plan_id')->constrained('plans')->cascadeOnDelete();
            $t->string('key');
            $t->boolean('is_enabled')->default(true);
            $t->json('limits')->nullable();
            $t->timestamps();
            $t->unique(['plan_id', 'key']);
        });

        Schema::create('tenant_usages', function (Blueprint $t) {
            $t->id();
            $t->string('tenant_id')->index();
            $t->string('metric');
            $t->string('period', 7);
            $t->unsignedBigInteger('used')->default(0);
            $t->unsignedBigInteger('limit')->nullable();
            $t->timestamps();
            $t->unique(['tenant_id', 'metric', 'period']);
        });

        // Form anti-spam (honeypot + rate tracking)
        Schema::create('form_spam_settings', function (Blueprint $t) {
            $t->id();
            $t->string('tenant_id')->nullable()->index();
            $t->boolean('honeypot')->default(true);
            $t->unsignedInteger('rate_limit_per_minute')->default(5);
            $t->unsignedInteger('min_fill_seconds')->default(2);
            $t->boolean('block_disposable_email')->default(false);
            $t->boolean('captcha')->default(false);
            $t->json('blocked_words')->nullable();
            $t->timestamps();
        });

        // Widgets (Appearance > Widgets)
        Schema::create('widgets', function (Blueprint $t) {
            $t->id();
            $t->string('tenant_id')->nullable()->index();
            $t->string('sidebar')->default('sidebar-1');
            $t->string('type');
            $t->string('title')->nullable();
            $t->json('config')->nullable();
            $t->integer('sort_order')->default(0);
            $t->boolean('is_visible')->default(true);
            $t->timestamps();
        });

        // Theme customizer values (header/footer/custom code)
        Schema::create('appearance_options', function (Blueprint $t) {
            $t->id();
            $t->string('tenant_id')->nullable()->index();
            $t->string('group')->index();
            $t->string('key');
            $t->text('value')->nullable();
            $t->string('type')->default('text');
            $t->timestamps();
            $t->unique(['group', 'key']);
        });

        // White label domains
        Schema::create('white_label_domains', function (Blueprint $t) {
            $t->id();
            $t->string('tenant_id')->nullable()->index();
            $t->string('domain')->unique();
            $t->boolean('is_primary')->default(false);
            $t->boolean('is_verified')->default(false);
            $t->timestamps();
        });
    }

    public function down(): void
    {
        foreach ([
            'white_label_domains', 'appearance_options', 'widgets', 'form_spam_settings',
            'tenant_usages', 'plan_features', 'notification_deliveries', 'activity_logs',
            'word_filters', 'comment_reports', 'seo_redirects',
        ] as $tb) {
            Schema::dropIfExists($tb);
        }
    }
};
