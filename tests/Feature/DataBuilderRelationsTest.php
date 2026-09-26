<?php

namespace Tests\Feature;

use App\Core\Services\RelationRegistry;
use App\Models\ContentRecord;
use App\Models\ContentType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The data builder shipped a RELATION_TYPES constant and a Relations screen
 * with nothing behind either. These prove a defined relation actually
 * resolves to real records, and that the admin can manage definitions.
 */
class DataBuilderRelationsTest extends TestCase
{
    use RefreshDatabase;

    protected function type(string $slug, array $fields = []): ContentType
    {
        return ContentType::create([
            'name' => ucfirst($slug), 'slug' => $slug,
            'fields' => $fields, 'is_api_enabled' => true,
        ]);
    }

    protected function record(ContentType $ct, array $data = []): ContentRecord
    {
        return ContentType::find($ct->id)->records()->create([
            'data' => $data, 'status' => 'published',
        ]);
    }

    protected function registry(): RelationRegistry
    {
        return app(RelationRegistry::class);
    }

    // ---- definitions --------------------------------------------------

    public function test_a_relation_can_be_defined(): void
    {
        $people = $this->type('people');
        $posts = $this->type('posts');

        $definition = $this->registry()->add($posts, [
            'name' => 'author', 'type' => 'belongsTo', 'target' => 'people',
        ]);

        $this->assertSame('author', $definition['name']);
        $this->assertSame('belongsTo', $definition['type']);
        $this->assertSame('people', $definition['target']);

        $this->assertSame(['author'], $posts->fresh()->relationNames());
    }


    public function test_an_unknown_relation_type_is_refused(): void
    {
        $people = $this->type('people');
        $posts = $this->type('posts');

        $this->expectException(\InvalidArgumentException::class);

        $this->registry()->add($posts, ['name' => 'x', 'type' => 'sideways', 'target' => 'people']);
    }

    public function test_a_relation_to_an_unknown_target_is_refused(): void
    {
        $posts = $this->type('posts');

        $this->expectException(\InvalidArgumentException::class);

        $this->registry()->add($posts, ['name' => 'x', 'type' => 'belongsTo', 'target' => 'nope']);
    }

    public function test_a_malformed_relation_name_is_refused(): void
    {
        $people = $this->type('people');
        $posts = $this->type('posts');

        $this->expectException(\InvalidArgumentException::class);

        $this->registry()->add($posts, ['name' => '9 bad name!', 'type' => 'belongsTo', 'target' => 'people']);
    }

    public function test_adding_the_same_name_replaces_rather_than_duplicates(): void
    {
        $people = $this->type('people');
        $posts = $this->type('posts');

        $this->registry()->add($posts, ['name' => 'author', 'type' => 'belongsTo', 'target' => 'people']);
        $this->registry()->add($posts, ['name' => 'author', 'type' => 'hasMany', 'target' => 'people']);

        $names = $posts->fresh()->relationNames();

        $this->assertSame(['author'], $names);
        $this->assertSame('hasMany', $posts->fresh()->relationDefinitions()[0]['type']);
    }

    public function test_removing_a_relation_drops_its_links(): void
    {
        $people = $this->type('people');
        $posts = $this->type('posts');
        $this->registry()->add($posts, ['name' => 'tags', 'type' => 'hasMany', 'target' => 'people']);

        $post = $this->record($posts);
        $tag = $this->record($people);
        $this->registry()->link($post, 'tags', $tag);

        $this->assertDatabaseCount('content_record_relations', 1);

        $this->registry()->remove($posts, 'tags');

        $this->assertDatabaseCount('content_record_relations', 0);
    }

    // ---- resolution ---------------------------------------------------

    public function test_belongs_to_resolves_a_single_record(): void
    {
        $people = $this->type('people');
        $posts = $this->type('posts');
        $this->registry()->add($posts, ['name' => 'author', 'type' => 'belongsTo', 'target' => 'people']);

        $author = $this->record($people, ['name' => 'Ana']);
        $post = $this->record($posts, ['title' => 'Hello']);

        $this->registry()->link($post, 'author', $author);

        $resolved = $this->registry()->resolve($post->fresh(), 'author');

        $this->assertInstanceOf(ContentRecord::class, $resolved);
        $this->assertSame('Ana', $resolved->data['name']);
    }

    public function test_has_many_resolves_a_collection(): void
    {
        $people = $this->type('people');
        $posts = $this->type('posts');
        $this->registry()->add($posts, ['name' => 'authors', 'type' => 'hasMany', 'target' => 'people']);

        $post = $this->record($posts);
        $a = $this->record($people, ['name' => 'A']);
        $b = $this->record($people, ['name' => 'B']);

        $this->registry()->link($post, 'authors', $a);
        $this->registry()->link($post, 'authors', $b);

        $resolved = $this->registry()->resolve($post->fresh(), 'authors');

        $this->assertCount(2, $resolved);
    }

    public function test_belongs_to_many_resolves_from_either_side(): void
    {
        $tags = $this->type('tags');
        $posts = $this->type('posts');
        $this->registry()->add($posts, ['name' => 'tags', 'type' => 'belongsToMany', 'target' => 'tags']);

        $post = $this->record($posts);
        $tag = $this->record($tags, ['label' => 'php']);

        $this->registry()->link($post, 'tags', $tag);

        $this->assertCount(1, $this->registry()->resolve($post->fresh(), 'tags'));
    }

    public function test_unlinking_removes_the_link(): void
    {
        $people = $this->type('people');
        $posts = $this->type('posts');
        $this->registry()->add($posts, ['name' => 'author', 'type' => 'belongsTo', 'target' => 'people']);

        $author = $this->record($people, ['name' => 'Ana']);
        $post = $this->record($posts);

        $this->registry()->link($post, 'author', $author);
        $this->registry()->unlink($post, 'author');

        $this->assertNull($this->registry()->resolve($post->fresh(), 'author'));
    }

    public function test_resolving_an_undefined_relation_returns_null(): void
    {
        $posts = $this->type('posts');
        $post = $this->record($posts);

        $this->assertNull($this->registry()->resolve($post, 'nope'));
    }

    public function test_with_relations_only_resolves_what_was_asked_for(): void
    {
        $people = $this->type('people');
        $tags = $this->type('tags');
        $posts = $this->type('posts');

        $this->registry()->add($posts, ['name' => 'author', 'type' => 'belongsTo', 'target' => 'people']);
        $this->registry()->add($posts, ['name' => 'tags', 'type' => 'hasMany', 'target' => 'tags']);

        $author = $this->record($people, ['name' => 'Ana']);
        $tag = $this->record($tags, ['label' => 'php']);
        $post = $this->record($posts, ['title' => 'T']);

        $this->registry()->link($post, 'author', $author);
        $this->registry()->link($post, 'tags', $tag);

        // Nothing requested: no relation keys at all.
        $plain = $this->registry()->withRelations($post);
        $this->assertArrayNotHasKey('author', $plain);

        // One requested: only that one.
        $partial = $this->registry()->withRelations($post, ['author']);
        $this->assertArrayHasKey('author', $partial);
        $this->assertArrayNotHasKey('tags', $partial);

        // Both requested.
        $full = $this->registry()->withRelations($post, ['author', 'tags']);
        $this->assertCount(1, $full['tags']);
    }

    public function test_the_model_exposes_a_relation_shortcut(): void
    {
        $people = $this->type('people');
        $posts = $this->type('posts');
        $this->registry()->add($posts, ['name' => 'author', 'type' => 'belongsTo', 'target' => 'people']);

        $author = $this->record($people, ['name' => 'Ana']);
        $post = $this->record($posts);
        $this->registry()->link($post, 'author', $author);

        $this->assertSame(['author'], $post->fresh()->relationNames());
        $this->assertSame('Ana', $post->fresh()->relation('author')->data['name']);
    }
}
