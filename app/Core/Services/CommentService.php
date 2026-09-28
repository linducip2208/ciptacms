<?php

namespace App\Core\Services;

use App\Models\Comment;
use App\Models\LoginHistory;
use App\Models\Post;
use App\Models\WordFilter;

/**
 * Public comment submission.
 *
 * The admin has always had Comments, Word Filter and Reports screens, but
 * nothing could actually post a comment. This is the missing write path, and
 * it is what makes the word filter mean anything.
 *
 * Moderation outcome, driven by the word filter:
 *   reject  -> not stored at all, visitor is told it was rejected
 *   hold    -> stored as `pending` for a human to approve
 *   spam    -> stored as `spam`
 *   (no match) -> stored as `pending`, or `approved` when auto-approve is on
 */
class CommentService
{
    public function submit(Post $post, array $input, string $ip, ?string $userAgent = null, $user = null): array
    {
        $matched = $this->matchWords((string) ($input['body'] ?? ''));

        $status = match ($matched['action'] ?? null) {
            'reject' => 'rejected',
            'spam' => 'spam',
            'hold' => 'pending',
            default => setting('comments.auto_approve', false) ? 'approved' : 'pending',
        };

        // A rejected comment is never stored; reporting it is enough.
        if ($status === 'rejected') {
            return [
                'stored' => false,
                'status' => $status,
                'message' => 'Your comment was rejected by the site filter.',
            ];
        }

        $comment = Comment::create([
            'tenant_id' => tenant_id(),
            'commentable_type' => Post::class,
            'commentable_id' => $post->id,
            'user_id' => $user?->id,
            'guest_name' => $user ? null : substr((string) ($input['author_name'] ?? 'Guest'), 0, 190),
            'guest_email' => $user ? null : substr((string) ($input['author_email'] ?? ''), 0, 190),
            'body' => (string) $input['body'],
            'status' => $status,
            'ip' => $ip,
            'user_agent' => $userAgent ? substr($userAgent, 0, 500) : null,
        ]);

        if ($matched['word'] ?? null) {
            // Record why it was held, so a moderator can see the reason.
            try {
                \App\Models\CommentReport::create([
                    'comment_id' => $comment->id,
                    'reporter_ip' => $ip,
                    'reason' => 'word filter: '.$matched['word'],
                    'status' => 'open',
                ]);
            } catch (\Throwable $e) {
                report($e);
            }
        }

        try {
            \App\Core\Support\DomainEvent::fire('comment.created', ['comment_id' => $comment->id, 'post_id' => $post->id, 'status' => $status]);
            app(WebhookDispatcher::class)->dispatchEvent('comment.created', [
                'post_id' => $post->id, 'comment_id' => $comment->id, 'status' => $status,
            ]);
            app(WorkflowEngine::class)->trigger('comment.created', [
                'post_id' => $post->id, 'comment_id' => $comment->id,
                'status' => $status, 'body' => $comment->body,
            ]);
        } catch (\Throwable $e) {
            report($e);
        }

        return [
            'stored' => true,
            'status' => $status,
            'comment' => $comment,
            'message' => match ($status) {
                'approved' => 'Thanks — your comment is published.',
                'spam' => 'Your comment was flagged for review.',
                default => 'Thanks — your comment is awaiting moderation.',
            },
        ];
    }

    /**
     * First active filter word found in the body.
     *
     * @return array{word:?string,action:?string}
     */
    public function matchWords(string $body): array
    {
        $haystack = mb_strtolower($body);

        try {
            $filters = WordFilter::active()->get();
        } catch (\Throwable $e) {
            return ['word' => null, 'action' => null];
        }

        foreach ($filters as $filter) {
            $word = mb_strtolower(trim((string) $filter->word));
            if ($word !== '' && str_contains($haystack, $word)) {
                return ['word' => $filter->word, 'action' => $filter->action];
            }
        }

        return ['word' => null, 'action' => null];
    }

    /** Let a visitor report a comment. */
    public function report(Comment $comment, ?string $reason, string $ip): \App\Models\CommentReport
    {
        return \App\Models\CommentReport::create([
            'comment_id' => $comment->id,
            'reporter_ip' => $ip,
            'reason' => $reason ? substr($reason, 0, 255) : null,
            'status' => 'open',
        ]);
    }

    /** Approved comments for a post, oldest first. */
    public function forPost(Post $post)
    {
        return Comment::published()
            ->where('commentable_type', Post::class)
            ->where('commentable_id', $post->id)
            ->orderBy('created_at')
            ->get();
    }
}
