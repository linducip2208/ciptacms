<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cp_services', function (Blueprint $t) {
            $t->id();
            $t->string('tenant_id')->nullable()->index();
            $t->string('title');
            $t->string('slug')->index();
            $t->string('icon')->nullable();
            $t->string('image')->nullable();
            $t->text('excerpt')->nullable();
            $t->longText('description')->nullable();
            $t->json('features')->nullable();
            $t->string('cta_label')->nullable();
            $t->string('cta_url')->nullable();
            $t->string('status')->default('published');
            $t->integer('sort_order')->default(0);
            $t->timestamps();
            $t->softDeletes();
        });

        Schema::create('cp_products', function (Blueprint $t) {
            $t->id();
            $t->string('tenant_id')->nullable()->index();
            $t->string('title');
            $t->string('slug')->index();
            $t->string('image')->nullable();
            $t->json('gallery')->nullable();
            $t->text('excerpt')->nullable();
            $t->longText('description')->nullable();
            $t->json('features')->nullable();
            $t->string('cta_label')->nullable();
            $t->string('cta_url')->nullable();
            $t->string('status')->default('published');
            $t->integer('sort_order')->default(0);
            $t->timestamps();
            $t->softDeletes();
        });

        Schema::create('cp_portfolios', function (Blueprint $t) {
            $t->id();
            $t->string('tenant_id')->nullable()->index();
            $t->string('title');
            $t->string('slug')->index();
            $t->string('client')->nullable();
            $t->string('category')->nullable();
            $t->date('project_date')->nullable();
            $t->text('excerpt')->nullable();
            $t->longText('description')->nullable();
            $t->json('technology')->nullable();
            $t->json('images')->nullable();
            $t->string('url')->nullable();
            $t->string('status')->default('published');
            $t->integer('sort_order')->default(0);
            $t->timestamps();
            $t->softDeletes();
        });

        Schema::create('cp_team', function (Blueprint $t) {
            $t->id();
            $t->string('tenant_id')->nullable()->index();
            $t->string('name');
            $t->string('slug')->index();
            $t->string('position')->nullable();
            $t->string('photo')->nullable();
            $t->text('bio')->nullable();
            $t->json('social')->nullable();
            $t->string('status')->default('published');
            $t->integer('sort_order')->default(0);
            $t->timestamps();
            $t->softDeletes();
        });

        Schema::create('cp_testimonials', function (Blueprint $t) {
            $t->id();
            $t->string('tenant_id')->nullable()->index();
            $t->string('customer');
            $t->string('company')->nullable();
            $t->string('photo')->nullable();
            $t->tinyInteger('rating')->default(5);
            $t->text('testimonial');
            $t->string('status')->default('published');
            $t->integer('sort_order')->default(0);
            $t->timestamps();
            $t->softDeletes();
        });

        Schema::create('cp_clients', function (Blueprint $t) {
            $t->id();
            $t->string('tenant_id')->nullable()->index();
            $t->string('name');
            $t->string('logo')->nullable();
            $t->string('website')->nullable();
            $t->string('status')->default('published');
            $t->integer('sort_order')->default(0);
            $t->timestamps();
            $t->softDeletes();
        });

        Schema::create('cp_faqs', function (Blueprint $t) {
            $t->id();
            $t->string('tenant_id')->nullable()->index();
            $t->string('question');
            $t->longText('answer');
            $t->string('category')->nullable();
            $t->string('status')->default('published');
            $t->integer('sort_order')->default(0);
            $t->timestamps();
            $t->softDeletes();
        });

        Schema::create('cp_gallery_albums', function (Blueprint $t) {
            $t->id();
            $t->string('tenant_id')->nullable()->index();
            $t->string('title');
            $t->string('slug')->index();
            $t->text('description')->nullable();
            $t->string('cover')->nullable();
            $t->string('status')->default('published');
            $t->integer('sort_order')->default(0);
            $t->timestamps();
            $t->softDeletes();
        });

        Schema::create('cp_gallery_images', function (Blueprint $t) {
            $t->id();
            $t->foreignId('album_id')->constrained('cp_gallery_albums')->cascadeOnDelete();
            $t->string('path');
            $t->string('caption')->nullable();
            $t->integer('sort_order')->default(0);
            $t->timestamps();
        });

        Schema::create('cp_careers', function (Blueprint $t) {
            $t->id();
            $t->string('tenant_id')->nullable()->index();
            $t->string('position');
            $t->string('slug')->index();
            $t->text('description')->nullable();
            $t->json('requirements')->nullable();
            $t->string('location')->nullable();
            $t->string('employment_type')->nullable();
            $t->date('deadline')->nullable();
            $t->string('status')->default('published');
            $t->integer('sort_order')->default(0);
            $t->timestamps();
            $t->softDeletes();
        });

        Schema::create('cp_job_applications', function (Blueprint $t) {
            $t->id();
            $t->foreignId('career_id')->constrained('cp_careers')->cascadeOnDelete();
            $t->string('name');
            $t->string('email');
            $t->string('phone')->nullable();
            $t->string('cv_path')->nullable();
            $t->text('cover_letter')->nullable();
            $t->string('status')->default('received');
            $t->string('ip')->nullable();
            $t->timestamps();
        });

        Schema::create('cp_contact_messages', function (Blueprint $t) {
            $t->id();
            $t->string('tenant_id')->nullable()->index();
            $t->string('name');
            $t->string('email');
            $t->string('phone')->nullable();
            $t->string('subject')->nullable();
            $t->text('message');
            $t->string('status')->default('new');
            $t->string('ip')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        foreach ([
            'cp_contact_messages', 'cp_job_applications', 'cp_careers',
            'cp_gallery_images', 'cp_gallery_albums', 'cp_faqs', 'cp_clients',
            'cp_testimonials', 'cp_team', 'cp_portfolios', 'cp_products',
            'cp_services',
        ] as $tb) {
            Schema::dropIfExists($tb);
        }
    }
};
