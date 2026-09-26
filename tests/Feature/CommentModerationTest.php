<?php

namespace Tests\Feature;

use App\Core\Services\CommentService;
use App\Core\Services\SettingService;
use App\Models\Comment;
use App\Models\Post;
use App\Models\WordFilter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The admin has always shipped Comments, Word Filter and Reports screens.
 * These tests prove the public write path they moderate actually exists and
 * that the filter changes the outcome.
 */
class CommentModerationTest extends TestCase
{
    use RefreshDatabase;

    protected function makePost(): Post
    {
        return Post::create([
            'title' => 'Discussable', 'slug' => 'discussable',
            'body' => '<p>Body</p>', 'status' => 'published',
            'published_at' => now(),
        ]);
    }

    protected function service(): CommentService
    {
        return app(CommentService::class);
    }

    // ---- submission ---------------------------------------------------

    public function test_a_visitor_can_post_a_comment(): void
    {
        $post = $this->makePost();

        $this->post(route('site.comments.store', $post), [
            'author_name' => 'Ana', 'author_email' => 'ana@example.com',
            'body' => 'Good read, thanks.',
        ])->assertRedirect();

        $this->assertDatabaseHas('comments', [
            'commentable_id' => $post->id,
            'body' => 'Good read, thanks.',
            'guest_name' => 'Ana',
        ]);
    }

    public function test_a_new_comment_is_held_by_default(): void
    {
        $post = $this->makePost();
        $this->service()->submit($post, ['body' => 'Held by default', 'author_name' => 'B'], '127.0.0.1');

        $this->assertDatabaseHas('comments', ['body' => 'Held by default', 'status' => 'pending']);
    }

    public function test_auto_approve_publishes_immediately_when_enabled(): void
    {
        app(SettingService::class)->set('comments.auto_approve', true, 'boolean', 'comments');

        $post = $this->makePost();
        $this->service()->submit($post, ['body' => 'Straight through', 'author_name' => 'C'], '127.0.0.1');

        $this->assertDatabaseHas('comments', ['body' => 'Straight through', 'status' => 'approved']);
    }

    public function test_a_comment_needs_a_body(): void
    {
        $this->post(route('site.comments.store', $this->makePost()), ['body' => ''])
            ->assertSessionHasErrors('body');

        $this->assertDatabaseCount('comments', 0);
    }

    public function test_the_honeypot_swallows_bot_posts(): void
    {
        $this->post(route('site.comments.store', $this->makePost()), [
            'author_name' => 'Bot', 'author_email' => 'bot@spam.example',
            'body' => 'BUY NOW cheap links', 'website' => 'http://spam.example',
        ])->assertRedirect();

        $this->assertDatabaseCount('comments', 0);
    }

    public function test_a_draft_post_cannot_be_commented_on(): void
    {
        $draft = Post::create(['title' => 'Draft', 'slug' => 'draft', 'body' => 'x', 'status' => 'draft']);

        $this->post(route('site.comments.store', $draft), ['body' => 'Hello', 'author_name' => 'D'])
            ->assertNotFound();
    }

    // ---- word filter --------------------------------------------------

    public function test_word_filter_holds_a_comment_for_review(): void
    {
        WordFilter::create(['word' => 'casino', 'action' => 'hold', 'is_active' => true]);

        $post = $this->makePost();
        $result = $this->service()->submit($post, ['body' => 'visit my casino tonight', 'author_name' => 'E'], '127.0.0.1');

        $this->assertTrue($result['stored']);
        $this->assertSame('pending', $result['status']);
        $this->assertDatabaseHas('comments', ['status' => 'pending']);
    }

    public function test_word_filter_flags_a_comment_as_spam(): void
    {
        WordFilter::create(['word' => 'viagra', 'action' => 'spam', 'is_active' => true]);

        $post = $this->makePost();
        $this->service()->submit($post, ['body' => 'buy VIAGRA now', 'author_name' => 'F'], '127.0.0.1');

        $this->assertDatabaseHas('comments', ['status' => 'spam']);
    }

    public function test_word_filter_rejects_a_comment_without_storing_it(): void
    {
        WordFilter::create(['word' => 'scam', 'action' => 'reject', 'is_active' => true]);

        $post = $this->makePost();
        $result = $this->service()->submit($post, ['body' => 'total scam here', 'author_name' => 'G'], '127.0.0.1');

        $this->assertFalse($result['stored']);
        $this->assertDatabaseCount('comments', 0);
    }

    public function test_a_held_comment_is_reported_with_a_reason(): void
    {
        WordFilter::create(['word' => 'casino', 'action' => 'hold', 'is_active' => true]);

        $post = $this->makePost();
        $this->service()->submit($post, ['body' => 'casino', 'author_name' => 'H'], '127.0.0.1');

        $this->assertDatabaseHas('comment_reports', ['status' => 'open']);
    }

    public function test_an_inactive_filter_word_is_ignored(): void
    {
        WordFilter::create(['word' => 'casino', 'action' => 'hold', 'is_active' => false]);

        $post = $this->makePost();
        $this->service()->submit($post, ['body' => 'a casino reference', 'author_name' => 'I'], '127.0.0.1');

        $this->assertDatabaseHas('comments', ['status' => 'pending']);
    }

    // ---- display ------------------------------------------------------

    public function test_only_approved_comments_are_shown_publicly(): void
    {
        $post = $this->makePost();
        $this->service()->submit($post, ['body' => 'pending one', 'author_name' => 'J'], '127.0.0.1');
        $this->service()->submit($post, ['body' => 'approved one', 'author_name' => 'K'], '127.0.0.1');
        Comment::where('body', 'approved one')->update(['status' => 'approved']);

        $this->get('/blog/'.$post->slug)
            ->assertOk()
            ->assertSee('approved one')
            ->assertDontSee('pending one');
    }

    public function test_a_visitor_can_report_a_comment(): void
    {
        $post = $this->makePost();
        $this->service()->submit($post, ['body' => 'report me', 'author_name' => 'L'], '127.0.0.1');
        $comment = Comment::firstOrFail();

        $this->post(route('site.comments.report', $comment), ['reason' => 'abusive'])
            ->assertRedirect();

        $this->assertDatabaseHas('comment_reports', ['comment_id' => $comment->id, 'reason' => 'abusive']);
    }

    public function test_posting_a_comment_is_rate_limited(): void
    {
        $post = $this->makePost();

        // The limiter allows 3/minute per IP. Spend it with real requests so
        // this exercises the middleware, not the cache directly.
        for ($i = 0; $i < 3; $i++) {
            $this->post(route('site.comments.store', $post), [
                'body' => 'comment '.$i, 'author_name' => 'M',
            ])->assertRedirect();
        }

        $this->post(route('site.comments.store', $post), [
            'body' => 'the fourth one', 'author_name' => 'M',
        ])->assertStatus(429);

        $this->assertDatabaseCount('comments', 3);
    }
}
