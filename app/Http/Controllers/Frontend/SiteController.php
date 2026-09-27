<?php

namespace App\Http\Controllers\Frontend;

use App\Core\Services\BlockLibrary;
use App\Core\Services\CompanyProfileService;
use App\Core\Services\SeoService;
use App\Http\Controllers\Controller;
use App\Models\Cp\Career;
use App\Models\Cp\Client;
use App\Models\Cp\Faq;
use App\Models\Cp\GalleryAlbum;
use App\Models\Cp\JobApplication;
use App\Models\Cp\Portfolio;
use App\Models\Cp\Product;
use App\Models\Cp\Service;
use App\Models\Cp\TeamMember;
use App\Models\Cp\Testimonial;
use App\Models\Page;
use App\Models\Comment;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SiteController extends Controller
{
    public function __construct(protected SeoService $seo)
    {
        // Layout resolution. An active theme that ships a views/ directory and
        // opts in with "views": true in its theme.json gets its directory
        // prepended to the view finder, so its site/layout.blade.php replaces
        // the shipped one (and any other site.* view it ships) without
        // resources/views being edited. Done once per request, and never
        // fatally: a missing theme folder leaves the core layout in place.
        try {
            app(\App\Core\Services\ThemeManager::class)->applyViewOverrides();
        } catch (\Throwable $e) {
            report($e);
        }
    }

    /**
     * Run a query, returning an empty result if the underlying table has not
     * been created yet. The public site is reachable between the installer
     * writing the database config and migrations completing, so it must not
     * fatal in that window.
     */
    protected function safe(callable $fn, $fallback = null)
    {
        try {
            return $fn();
        } catch (\Illuminate\Database\QueryException $e) {
            return $fallback;
        }
    }

    protected function safeCollection(string $model, callable $apply = null)
    {
        return $this->safe(function () use ($model, $apply) {
            $q = $model::query();
            if ($apply) {
                $q = $apply($q);
            }

            return $q->get();
        }, collect());
    }

    protected function view(string $name, array $data = [], array $meta = [])
    {
        $meta = array_merge([
            'title' => null,
            'description' => null,
            'canonical' => url()->current(),
            'og_image' => null,
            'schema' => null,
        ], $meta);

        $profile = app(CompanyProfileService::class);

        return view('site.'.$name, array_merge($data, [
            'seo' => $meta,
            'about' => $this->safe(fn () => $profile->about(), []),
            'contactInfo' => $this->safe(fn () => $profile->contact(), []),
        ]));
    }

    public function home()
    {
        $page = $this->safe(fn () => Page::published()->homepage()->first());
        if ($page) {
            return $this->renderPage($page, 'site.home');
        }

        return $this->view('home', [
            'services' => $this->safeCollection(Service::class, fn ($q) => $q->published()->ordered()->limit(6)),
            'portfolio' => $this->safeCollection(\App\Models\Cp\Portfolio::class, fn ($q) => $q->published()->ordered()->limit(6)),
            'posts' => $this->safeCollection(Post::class, fn ($q) => $q->where('status', 'published')->latest('published_at')->limit(3)),
            'testimonials' => $this->safeCollection(Testimonial::class, fn ($q) => $q->published()->ordered()->limit(6)),
            'clients' => $this->safeCollection(Client::class, fn ($q) => $q->published()->ordered()->limit(12)),
            'stats' => [
                'services' => $this->safe(fn () => Service::published()->count(), 0),
                'projects' => $this->safe(fn () => Portfolio::published()->count(), 0),
                'clients' => $this->safe(fn () => Client::published()->count(), 0),
                'team' => $this->safe(fn () => TeamMember::published()->count(), 0),
            ],
        ]);
    }

    protected function renderPage(Page $page, string $view = 'site.page')
    {
        $data = ['page' => $page];

        if ($page->builder) {
            $data['rendered'] = BlockLibrary::render($page->builder);
        }

        $profile = app(CompanyProfileService::class);

        return view($view, array_merge($data, [
            'seo' => $this->safe(fn () => $this->seo->for(Page::class, $page->id), []),
            'about' => $this->safe(fn () => $profile->about(), []),
            'contactInfo' => $this->safe(fn () => $profile->contact(), []),
        ]));
    }

    public function about()
    {
        return $this->view('about', [
            'team' => $this->safeCollection(TeamMember::class, fn ($q) => $q->published()->ordered()->limit(8)),
            'stats' => [
                'years' => max(1, (int) date('Y') - (int) (setting('general.founded_year') ?: (int) date('Y'))),
                'projects' => $this->safe(fn () => Portfolio::published()->count(), 0),
                'clients' => $this->safe(fn () => Client::published()->count(), 0),
            ],
        ]);
    }

    public function services()
    {
        return $this->view('services', [
            'services' => $this->safeCollection(Service::class, fn ($q) => $q->published()->ordered()),
        ]);
    }

    public function service(string $slug)
    {
        $service = Service::published()->where('slug', $slug)->firstOrFail();

        return $this->view('service-detail', [
            'item' => $service,
            'related' => Service::published()->ordered()->where('id', '!=', $service->id)->limit(3)->get(),
        ], [
            'title' => $this->seo->titleFor($service, $service->title),
            'description' => $service->excerpt,
        ]);
    }

    public function products()
    {
        return $this->view('products', [
            'products' => $this->safeCollection(Product::class, fn ($q) => $q->published()->ordered()),
        ]);
    }

    public function product(string $slug)
    {
        $product = Product::published()->where('slug', $slug)->firstOrFail();

        return $this->view('product-detail', [
            'item' => $product,
            'related' => Product::published()->ordered()->where('id', '!=', $product->id)->limit(3)->get(),
        ], [
            'title' => $this->seo->titleFor($product, $product->title),
            'description' => $product->excerpt,
        ]);
    }

    public function portfolio(Request $r)
    {
        $q = Portfolio::published()->ordered();
        if ($category = $r->get('category')) {
            $q->where('category', $category);
        }
        if ($search = $r->get('search')) {
            $q->where(function ($w) use ($search) {
                $w->where('title', 'like', "%{$search}%")
                    ->orWhere('client', 'like', "%{$search}%")
                    ->orWhere('category', 'like', "%{$search}%");
            });
        }

        return $this->view('portfolio', [
            'rows' => $q->paginate(12)->withQueryString(),
            'categories' => Portfolio::published()->whereNotNull('category')->distinct()->pluck('category')->filter(),
        ]);
    }

    public function portfolioItem(string $slug)
    {
        $item = Portfolio::published()->where('slug', $slug)->firstOrFail();

        return $this->view('portfolio-detail', [
            'item' => $item,
            'related' => Portfolio::published()->ordered()->where('id', '!=', $item->id)->limit(3)->get(),
        ], [
            'title' => $this->seo->titleFor($item, $item->title),
            'description' => $item->excerpt,
        ]);
    }

    public function team()
    {
        return $this->view('team', [
            'members' => $this->safeCollection(TeamMember::class, fn ($q) => $q->published()->ordered()),
        ]);
    }

    public function testimonials()
    {
        return $this->view('testimonials', [
            'rows' => $this->safeCollection(Testimonial::class, fn ($q) => $q->published()->ordered()),
        ]);
    }

    public function clients()
    {
        return $this->view('clients', [
            'rows' => $this->safeCollection(Client::class, fn ($q) => $q->published()->ordered()),
        ]);
    }

    public function faq()
    {
        $grouped = $this->safe(fn () => Faq::published()->ordered()->get()->groupBy(fn ($f) => $f->category ?: 'General'), collect());

        return $this->view('faq', [
            'grouped' => $grouped,
            'categories' => $grouped->keys(),
        ]);
    }

    public function gallery()
    {
        return $this->view('gallery', [
            'albums' => $this->safeCollection(GalleryAlbum::class, fn ($q) => $q->published()->ordered()->with('images')),
            'current' => null,
        ]);
    }

    public function galleryAlbum(string $slug)
    {
        $album = GalleryAlbum::published()->where('slug', $slug)->with('images')->firstOrFail();

        return $this->view('gallery', [
            'albums' => $this->safeCollection(GalleryAlbum::class, fn ($q) => $q->published()->ordered()->with('images')),
            'current' => $album,
        ], [
            'title' => $this->seo->titleFor($album, $album->title),
            'description' => Str::limit(strip_tags((string) $album->description), 160),
        ]);
    }

    public function careers()
    {
        $q = $this->safe(fn () => Career::published()->ordered());
        if ($search = request('search')) {
            $q = $this->safe(fn () => Career::published()->ordered()->where('position', 'like', "%{$search}%"));
        }
        if ($location = request('location')) {
            $q = $this->safe(fn () => $q->where('location', $location));
        }

        return $this->view('careers', [
            'rows' => $this->safe(fn () => $q->paginate(15)->withQueryString()),
            'locations' => $this->safe(fn () => Career::published()->whereNotNull('location')->distinct()->pluck('location')->filter(), collect()),
        ]);
    }

    public function career(string $slug)
    {
        $career = Career::published()->where('slug', $slug)->firstOrFail();

        return $this->view('career-detail', [
            'item' => $career,
        ], [
            'title' => $this->seo->titleFor($career, $career->position.' — '.$career->location),
            'description' => Str::limit(strip_tags((string) $career->description), 160),
        ]);
    }

    public function apply(Request $r, Career $career)
    {
        if ($career->deadline && $career->deadline->isPast()) {
            throw ValidationException::withMessages([
                'career' => 'This position is already closed.',
            ]);
        }

        $data = $r->validate([
            'name' => 'required|string|max:190',
            'email' => 'required|email|max:190',
            'phone' => 'nullable|string|max:60',
            'cover_letter' => 'nullable|string|max:5000',
            'cv' => 'nullable|file|mimes:pdf,doc,docx|max:4096',
        ]);

        $path = null;
        if ($r->hasFile('cv')) {
            $path = $r->file('cv')->store('careers', 'public');
        }

        $application = JobApplication::create([
            'career_id' => $career->id,
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'cover_letter' => $data['cover_letter'] ?? null,
            'cv_path' => $path,
            'status' => 'received',
            'ip' => $r->ip(),
        ]);

        $this->notifyNew('job.application', [
            'position' => $career->position,
            'applicant' => $application->name,
            'email' => $application->email,
        ]);

        return back()->with('ok', 'Thank you. Your application has been received.');
    }

    public function contact()
    {
        return $this->view('contact', [
            'services' => $this->safe(fn () => Service::published()->ordered()->pluck('title', 'id'), collect()),
        ]);
    }

    public function submitContact(Request $r)
    {
        $data = $r->validate([
            'name' => 'required|string|max:190',
            'email' => 'required|email|max:190',
            'phone' => 'nullable|string|max:60',
            'subject' => 'nullable|string|max:190',
            'message' => 'required|string|max:5000',
            'website' => 'nullable|max:0', // honeypot
        ]);

        $message = \App\Models\Cp\ContactMessage::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'subject' => $data['subject'] ?? null,
            'message' => $data['message'],
            'status' => 'new',
            'ip' => $r->ip(),
        ]);

        $this->notifyNew('contact.message', [
            'name' => $message->name,
            'email' => $message->email,
            'subject' => $message->subject,
        ]);

        return back()->with('ok', 'Thank you. Your message has been sent.');
    }

    public function blog()
    {
        $posts = $this->safe(fn () => Post::where('status', 'published')
            ->with(['category', 'author'])
            ->latest('published_at')
            ->paginate(12));

        return view('site.blog', [
            'posts' => $posts ?? new \Illuminate\Pagination\LengthAwarePaginator([], 0, 12),
            'seo' => ['title' => 'Blog', 'description' => null, 'canonical' => url()->current(), 'og_image' => null, 'schema' => null],
            'about' => $this->safe(fn () => app(CompanyProfileService::class)->about(), []),
            'contactInfo' => $this->safe(fn () => app(CompanyProfileService::class)->contact(), []),
        ]);
    }

    public function post(string $slug)
    {
        $post = Post::where('slug', $slug)->where('status', 'published')->firstOrFail();
        $post->increment('views');

        $seo = $this->seo->for(Post::class, $post->id);

        return view('site.post', [
            'post' => $post,
            'comments' => app(\App\Core\Services\CommentService::class)->forPost($post),
            'seo' => array_merge([
                'title' => $post->title,
                'description' => Str::limit(strip_tags((string) $post->excerpt), 160),
                'canonical' => url()->current(),
                'og_image' => $post->featured_image,
                'schema' => null,
            ], array_filter($seo)),
            'about' => app(CompanyProfileService::class)->about(),
            'contactInfo' => app(CompanyProfileService::class)->contact(),
            'related' => Post::where('status', 'published')
                ->where('id', '!=', $post->id)
                ->when($post->category_id, fn ($q) => $q->where('category_id', $post->category_id))
                ->latest('published_at')
                ->limit(3)
                ->get(),
        ]);
    }

    /**
     * Public comment submission. The word filter decides whether the comment
     * is published, held, flagged as spam or refused outright.
     */
    public function storeComment(Request $r, Post $post)
    {
        abort_if($post->status !== 'published', 404);

        $data = $r->validate([
            'body' => 'required|string|min:3|max:5000',
            'author_name' => 'nullable|string|max:190',
            'author_email' => 'nullable|email|max:190',
            // Honeypot: a real visitor never fills this.
            'website' => 'nullable|max:0',
        ]);

        if (! empty($data['website'])) {
            return back()->with('ok', 'Thanks — your comment is awaiting moderation.');
        }

        $result = app(\App\Core\Services\CommentService::class)->submit(
            $post,
            $data,
            $r->ip(),
            $r->userAgent(),
            $r->user()
        );

        return back()->with($result['stored'] ? 'ok' : 'error', $result['message']);
    }

    public function reportComment(Request $r, Comment $comment)
    {
        $data = $r->validate(['reason' => 'nullable|string|max:255']);

        app(\App\Core\Services\CommentService::class)->report($comment, $data['reason'] ?? null, $r->ip());

        return back()->with('ok', 'Reported. A moderator will review it.');
    }

    public function page(string $slug)
    {
        $page = Page::where('slug', $slug)->where('status', 'published')->firstOrFail();
        $page->increment('views');

        return $this->renderPage($page);
    }

    protected function notifyNew(string $event, array $payload): void
    {
        try {
            event('cms.'.$event, $payload);
            app(\App\Core\Services\WebhookDispatcher::class)->dispatchEvent($event, $payload);
            app(\App\Core\Services\WorkflowEngine::class)->trigger($event, $payload);
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
