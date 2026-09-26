<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Relations for dynamic content types.
 *
 * The data builder shipped a RELATION_TYPES constant and a Relations screen,
 * but there was nowhere to store a relation and nothing ever resolved one.
 * `relations` holds the operator's definitions; the pivot table backs
 * belongsToMany. See App\Core\Services\RelationRegistry.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('content_types') && ! Schema::hasColumn('content_types', 'relation_definitions')) {
            Schema::table('content_types', function (Blueprint $t) {
                $t->json('relation_definitions')->nullable()->after('fields');
            });
        }

        if (! Schema::hasTable('content_record_relations')) {
            Schema::create('content_record_relations', function (Blueprint $t) {
                $t->id();

                // Polymorphic: a relation can point at a company-profile model
                // as well as at another dynamic record.
                $t->string('record_type');
                $t->unsignedBigInteger('record_id');
                $t->string('related_type');
                $t->unsignedBigInteger('related_id');
                $t->string('relation');
                $t->timestamps();

                $t->unique(
                    ['record_type', 'record_id', 'related_type', 'related_id', 'relation'],
                    'content_record_relations_unique'
                );
                $t->index(['record_type', 'record_id', 'relation']);
                $t->index(['related_type', 'related_id', 'relation']);
            });
        }

        // ContentRecord uses SoftDeletes; the column must exist.
        if (Schema::hasTable('content_records') && ! Schema::hasColumn('content_records', 'deleted_at')) {
            Schema::table('content_records', function (Blueprint $t) {
                $t->softDeletes();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('content_records') && Schema::hasColumn('content_records', 'deleted_at')) {
            Schema::table('content_records', function (Blueprint $t) {
                $t->dropSoftDeletes();
            });
        }

        Schema::dropIfExists('content_record_relations');

        if (Schema::hasTable('content_types') && Schema::hasColumn('content_types', 'relation_definitions')) {
            Schema::table('content_types', function (Blueprint $t) {
                $t->dropColumn('relations');
            });
        }
    }
};
