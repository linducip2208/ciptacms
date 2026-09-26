<?php

namespace App\Http\Controllers\Admin;

use App\Core\Services\CompanyProfileService;
use App\Models\Cp\Career;
use App\Models\Cp\Client;
use App\Models\Cp\ContactMessage;
use App\Models\Cp\Faq;
use App\Models\Cp\GalleryAlbum;
use App\Models\Cp\GalleryImage;
use App\Models\Cp\JobApplication;
use App\Models\Cp\Portfolio;
use App\Models\Cp\Product;
use App\Models\Cp\Service;
use App\Models\Cp\TeamMember;
use App\Models\Cp\Testimonial;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CompanyProfileController extends AdminController
{
    /**
     * Whitelist of editable company resources. The request {resource} slug is
     * resolved only through this map — never used to build a class name.
     */
    public const RESOURCES = [
        'services' => [
            'model' => Service::class,
            'label' => 'Services',
            'singular' => 'Service',
            'search' => ['title', 'excerpt'],
            'columns' => ['title', 'icon', 'excerpt', 'status', 'sort_order', 'actions'],
            'fields' => [
                'title' => ['type' => 'text', 'rules' => 'required|string|max:190', 'col' => 12],
                'icon' => ['type' => 'text', 'rules' => 'nullable|string|max:100', 'col' => 4, 'help' => 'Emoji or short icon name'],
                'image' => ['type' => 'image', 'rules' => 'nullable|string|max:255', 'col' => 8],
                'excerpt' => ['type' => 'textarea', 'rules' => 'nullable|string|max:500', 'col' => 12],
                'description' => ['type' => 'richtext', 'rules' => 'nullable|string', 'col' => 12],
                'features' => ['type' => 'lines', 'rules' => 'nullable', 'col' => 12, 'help' => 'One feature per line'],
                'cta_label' => ['type' => 'text', 'rules' => 'nullable|string|max:100', 'col' => 6],
                'cta_url' => ['type' => 'text', 'rules' => 'nullable|string|max:255', 'col' => 6],
                'sort_order' => ['type' => 'number', 'rules' => 'nullable|integer', 'col' => 4],
                'status' => ['type' => 'select', 'rules' => 'nullable|in:published,draft', 'col' => 4, 'options' => ['published' => 'Published', 'draft' => 'Draft']],
            ],
        ],
        'products' => [
            'model' => Product::class,
            'label' => 'Products',
            'singular' => 'Product',
            'search' => ['title', 'excerpt'],
            'columns' => ['title', 'image', 'excerpt', 'status', 'sort_order', 'actions'],
            'fields' => [
                'title' => ['type' => 'text', 'rules' => 'required|string|max:190', 'col' => 12],
                'image' => ['type' => 'image', 'rules' => 'nullable|string|max:255', 'col' => 6],
                'gallery' => ['type' => 'lines', 'rules' => 'nullable', 'col' => 6, 'help' => 'One image URL per line'],
                'excerpt' => ['type' => 'textarea', 'rules' => 'nullable|string|max:500', 'col' => 12],
                'description' => ['type' => 'richtext', 'rules' => 'nullable|string', 'col' => 12],
                'features' => ['type' => 'lines', 'rules' => 'nullable', 'col' => 12],
                'cta_label' => ['type' => 'text', 'rules' => 'nullable|string|max:100', 'col' => 6],
                'cta_url' => ['type' => 'text', 'rules' => 'nullable|string|max:255', 'col' => 6],
                'sort_order' => ['type' => 'number', 'rules' => 'nullable|integer', 'col' => 4],
                'status' => ['type' => 'select', 'rules' => 'nullable|in:published,draft', 'col' => 4, 'options' => ['published' => 'Published', 'draft' => 'Draft']],
            ],
        ],
        'portfolio' => [
            'model' => Portfolio::class,
            'label' => 'Portfolio',
            'singular' => 'Portfolio Item',
            'search' => ['title', 'client', 'category'],
            'columns' => ['title', 'client', 'category', 'project_date', 'status', 'actions'],
            'fields' => [
                'title' => ['type' => 'text', 'rules' => 'required|string|max:190', 'col' => 8],
                'client' => ['type' => 'text', 'rules' => 'nullable|string|max:190', 'col' => 4],
                'category' => ['type' => 'text', 'rules' => 'nullable|string|max:190', 'col' => 4],
                'project_date' => ['type' => 'date', 'rules' => 'nullable|date', 'col' => 4],
                'url' => ['type' => 'text', 'rules' => 'nullable|string|max:255', 'col' => 4],
                'excerpt' => ['type' => 'textarea', 'rules' => 'nullable|string|max:500', 'col' => 12],
                'description' => ['type' => 'richtext', 'rules' => 'nullable|string', 'col' => 12],
                'technology' => ['type' => 'lines', 'rules' => 'nullable', 'col' => 6, 'help' => 'One technology per line'],
                'images' => ['type' => 'lines', 'rules' => 'nullable', 'col' => 6, 'help' => 'One image URL per line'],
                'sort_order' => ['type' => 'number', 'rules' => 'nullable|integer', 'col' => 4],
                'status' => ['type' => 'select', 'rules' => 'nullable|in:published,draft', 'col' => 4, 'options' => ['published' => 'Published', 'draft' => 'Draft']],
            ],
        ],
        'team' => [
            'model' => TeamMember::class,
            'label' => 'Team',
            'singular' => 'Team Member',
            'search' => ['name', 'position'],
            'columns' => ['name', 'position', 'photo', 'status', 'sort_order', 'actions'],
            'fields' => [
                'name' => ['type' => 'text', 'rules' => 'required|string|max:190', 'col' => 8],
                'position' => ['type' => 'text', 'rules' => 'nullable|string|max:190', 'col' => 4],
                'photo' => ['type' => 'image', 'rules' => 'nullable|string|max:255', 'col' => 6],
                'social' => ['type' => 'lines', 'rules' => 'nullable', 'col' => 6, 'help' => 'One per line as network|url, e.g. linkedin|https://…'],
                'bio' => ['type' => 'textarea', 'rules' => 'nullable|string', 'col' => 12],
                'sort_order' => ['type' => 'number', 'rules' => 'nullable|integer', 'col' => 4],
                'status' => ['type' => 'select', 'rules' => 'nullable|in:published,draft', 'col' => 4, 'options' => ['published' => 'Published', 'draft' => 'Draft']],
            ],
        ],
        'testimonials' => [
            'model' => Testimonial::class,
            'label' => 'Testimonials',
            'singular' => 'Testimonial',
            'search' => ['customer', 'company'],
            'columns' => ['customer', 'company', 'rating', 'status', 'sort_order', 'actions'],
            'fields' => [
                'customer' => ['type' => 'text', 'rules' => 'required|string|max:190', 'col' => 8],
                'company' => ['type' => 'text', 'rules' => 'nullable|string|max:190', 'col' => 4],
                'photo' => ['type' => 'image', 'rules' => 'nullable|string|max:255', 'col' => 4],
                'rating' => ['type' => 'number', 'rules' => 'nullable|integer|between:1,5', 'col' => 4],
                'sort_order' => ['type' => 'number', 'rules' => 'nullable|integer', 'col' => 4],
                'testimonial' => ['type' => 'textarea', 'rules' => 'required|string', 'col' => 12],
                'status' => ['type' => 'select', 'rules' => 'nullable|in:published,draft', 'col' => 4, 'options' => ['published' => 'Published', 'draft' => 'Draft']],
            ],
        ],
        'clients' => [
            'model' => Client::class,
            'label' => 'Clients',
            'singular' => 'Client',
            'search' => ['name'],
            'columns' => ['name', 'logo', 'website', 'status', 'sort_order', 'actions'],
            'fields' => [
                'name' => ['type' => 'text', 'rules' => 'required|string|max:190', 'col' => 6],
                'website' => ['type' => 'text', 'rules' => 'nullable|string|max:255', 'col' => 6],
                'logo' => ['type' => 'image', 'rules' => 'nullable|string|max:255', 'col' => 6],
                'sort_order' => ['type' => 'number', 'rules' => 'nullable|integer', 'col' => 6],
                'status' => ['type' => 'select', 'rules' => 'nullable|in:published,draft', 'col' => 12, 'options' => ['published' => 'Published', 'draft' => 'Draft']],
            ],
        ],
        'faqs' => [
            'model' => Faq::class,
            'label' => 'FAQ',
            'singular' => 'FAQ Item',
            'search' => ['question', 'category'],
            'columns' => ['question', 'category', 'status', 'sort_order', 'actions'],
            'fields' => [
                'question' => ['type' => 'text', 'rules' => 'required|string|max:255', 'col' => 8],
                'category' => ['type' => 'text', 'rules' => 'nullable|string|max:190', 'col' => 4],
                'sort_order' => ['type' => 'number', 'rules' => 'nullable|integer', 'col' => 4],
                'status' => ['type' => 'select', 'rules' => 'nullable|in:published,draft', 'col' => 4, 'options' => ['published' => 'Published', 'draft' => 'Draft']],
                'answer' => ['type' => 'textarea', 'rules' => 'required|string', 'col' => 12],
            ],
        ],
        'careers' => [
            'model' => Career::class,
            'label' => 'Careers',
            'singular' => 'Position',
            'search' => ['position', 'location'],
            'columns' => ['position', 'location', 'employment_type', 'deadline', 'status', 'actions'],
            'fields' => [
                'position' => ['type' => 'text', 'rules' => 'required|string|max:190', 'col' => 8],
                'location' => ['type' => 'text', 'rules' => 'nullable|string|max:190', 'col' => 4],
                'employment_type' => ['type' => 'select', 'rules' => 'nullable|string|max:100', 'col' => 6, 'options' => ['Full-time' => 'Full-time', 'Part-time' => 'Part-time', 'Contract' => 'Contract', 'Internship' => 'Internship', 'Freelance' => 'Freelance']],
                'deadline' => ['type' => 'date', 'rules' => 'nullable|date', 'col' => 6],
                'sort_order' => ['type' => 'number', 'rules' => 'nullable|integer', 'col' => 4],
                'status' => ['type' => 'select', 'rules' => 'nullable|in:published,draft', 'col' => 4, 'options' => ['published' => 'Published', 'draft' => 'Draft']],
                'description' => ['type' => 'richtext', 'rules' => 'nullable|string', 'col' => 12],
                'requirements' => ['type' => 'lines', 'rules' => 'nullable', 'col' => 12, 'help' => 'One requirement per line'],
            ],
        ],
    ];

    /** Fields stored as JSON arrays. */
    public const ARRAY_FIELDS = ['features', 'technology', 'images', 'gallery', 'requirements'];

    /** Fields rendered as a textarea in the admin form. */
    public const RICH_FIELDS = ['description', 'body'];

    protected function resource(string $slug): array
    {
        if (! isset(self::RESOURCES[$slug])) {
            abort(404, 'Unknown company resource: '.$slug);
        }

        return self::RESOURCES[$slug];
    }

    // ------------------------------------------------------------------
    // Dashboard / About / Contact
    // ------------------------------------------------------------------

    public function home()
    {
        return view('admin.company.home', [
            'counts' => [
                'services' => Service::count(),
                'products' => Product::count(),
                'portfolio' => Portfolio::count(),
                'team' => TeamMember::count(),
                'testimonials' => Testimonial::count(),
                'clients' => Client::count(),
                'faqs' => Faq::count(),
                'albums' => GalleryAlbum::count(),
                'careers' => Career::count(),
                'messages' => ContactMessage::where('status', 'new')->count(),
                'applications' => JobApplication::where('status', 'received')->count(),
            ],
            'recentMessages' => ContactMessage::latest()->limit(5)->get(),
            'recentApplications' => JobApplication::with('career')->latest()->limit(5)->get(),
        ]);
    }

    public function about()
    {
        return view('admin.company.about', [
            'about' => app(CompanyProfileService::class)->about(),
        ]);
    }

    public function saveAbout(Request $r)
    {
        $data = $r->validate([
            'about.description' => 'nullable|string',
            'about.history' => 'nullable|string',
            'about.vision' => 'nullable|string',
            'about.mission' => 'nullable|string',
            'about.values' => 'nullable',
        ]);

        $data['about.values'] = $this->toArray($data['about.values'] ?? []);
        app(CompanyProfileService::class)->saveAbout($data);
        $this->audit('update_company_about', null, $r);

        return back()->with('ok', 'About page saved');
    }

    public function contactSettings()
    {
        return view('admin.company.contact', [
            'contact' => app(CompanyProfileService::class)->contact(),
        ]);
    }

    public function saveContactSettings(Request $r)
    {
        $data = $r->validate([
            'contact.address' => 'nullable|string|max:500',
            'contact.phone' => 'nullable|string|max:60',
            'contact.whatsapp' => 'nullable|string|max:60',
            'contact.email' => 'nullable|email|max:190',
            'contact.map_embed' => 'nullable|string|max:2000',
            'contact.business_hours' => 'nullable|string|max:500',
            'contact.social' => 'nullable',
        ]);

        if (isset($data['contact.social'])) {
            $data['contact.social'] = $this->socialPairs($data['contact.social']);
        }

        app(CompanyProfileService::class)->saveContact($data);
        $this->audit('update_company_contact', null, $r);

        return back()->with('ok', 'Contact settings saved');
    }

    public function messages(Request $r)
    {
        $q = ContactMessage::query()->latest();
        if ($status = $r->get('status')) {
            $q->where('status', $status);
        }
        if ($search = $r->get('search')) {
            $q->where(function ($w) use ($search) {
                $w->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('subject', 'like', "%{$search}%");
            });
        }

        return view('admin.company.messages', ['rows' => $q->paginate(20)->withQueryString()]);
    }

    public function messageStatus(Request $r, ContactMessage $message)
    {
        $message->update($r->validate(['status' => 'required|in:new,read,replied,archived'])['status']);
        $this->audit('update_contact_message', $message, $r);

        return back()->with('ok', 'Message status updated');
    }

    public function destroyMessage(Request $r, ContactMessage $message)
    {
        $message->delete();
        $this->audit('delete_contact_message', $message, $r);

        return back()->with('ok', 'Message deleted');
    }

    public function applications(Request $r)
    {
        $q = JobApplication::with('career')->latest();
        if ($status = $r->get('status')) {
            $q->where('status', $status);
        }

        return view('admin.company.applications', ['rows' => $q->paginate(20)->withQueryString()]);
    }

    public function applicationStatus(Request $r, JobApplication $application)
    {
        $application->update($r->validate(['status' => 'required|in:received,reviewing,shortlisted,rejected,hired'])['status']);
        $this->audit('update_job_application', $application, $r);

        return back()->with('ok', 'Application status updated');
    }

    // ------------------------------------------------------------------
    // Generic CRUD
    // ------------------------------------------------------------------

    public function index(Request $r, string $resource)
    {
        $def = $this->resource($resource);
        $model = $def['model'];
        $q = $model::query();

        if ($search = $r->get('search')) {
            $cols = $def['search'] ?? [];
            $q->where(function ($w) use ($cols, $search) {
                foreach ($cols as $c) {
                    $w->orWhere($c, 'like', "%{$search}%");
                }
            });
        }
        if ($status = $r->get('status')) {
            $q->where('status', $status);
        }
        if ($sort = $r->get('sort')) {
            $dir = $r->get('dir') === 'asc' ? 'asc' : 'desc';
            $q->orderBy(Str::snake($sort), $dir);
        } else {
            $q->orderBy('sort_order')->orderByDesc('id');
        }

        return view('admin.company.crud', [
            'def' => $def,
            'resource' => $resource,
            'rows' => $q->paginate(20)->withQueryString(),
        ]);
    }

    public function trash(Request $r, string $resource)
    {
        $def = $this->resource($resource);
        $model = $def['model'];

        return view('admin.company.trash', [
            'def' => $def,
            'resource' => $resource,
            'rows' => $model::onlyTrashed()->orderByDesc('deleted_at')->paginate(20)->withQueryString(),
        ]);
    }

    public function create(string $resource)
    {
        $def = $this->resource($resource);
        $row = new $def['model'];

        return view('admin.company.form', [
            'def' => $def,
            'resource' => $resource,
            'row' => $row,
            'action' => route('admin.company.store', $resource),
            'method' => 'POST',
        ]);
    }

    public function edit(string $resource, $id)
    {
        $def = $this->resource($resource);
        $model = $def['model'];
        $row = $model::withTrashed()->findOrFail($id);

        return view('admin.company.form', [
            'def' => $def,
            'resource' => $resource,
            'row' => $row,
            'action' => route('admin.company.update', [$resource, $row->getKey()]),
            'method' => 'PUT',
        ]);
    }

    public function store(Request $r, string $resource)
    {
        $def = $this->resource($resource);
        $row = new $def['model'];
        $row->fill($this->payload($r, $def));
        if (! $row->status) {
            $row->status = 'published';
        }
        $row->save();
        $this->saveSeo($row, $r);
        $this->audit('create', $row, $r);

        return back()->with('ok', $def['singular'].' created');
    }

    public function update(Request $r, string $resource, $id)
    {
        $def = $this->resource($resource);
        $model = $def['model'];
        $row = $model::withTrashed()->findOrFail($id);

        $row->fill($this->payload($r, $def));
        $row->save();
        $this->saveSeo($row, $r);
        $this->audit('update', $row, $r);

        return back()->with('ok', $def['singular'].' updated');
    }

    public function destroy(Request $r, string $resource, $id)
    {
        $def = $this->resource($resource);
        $row = $def['model']::findOrFail($id);
        $row->delete();
        $this->audit('delete', $row, $r);

        return back()->with('ok', $def['singular'].' moved to trash');
    }

    public function restore(Request $r, string $resource, $id)
    {
        $def = $this->resource($resource);
        $row = $def['model']::onlyTrashed()->findOrFail($id);
        $row->restore();
        $this->audit('restore', $row, $r);

        return back()->with('ok', $def['singular'].' restored');
    }

    public function duplicate(Request $r, string $resource, $id)
    {
        $def = $this->resource($resource);
        $row = $def['model']::findOrFail($id);
        $copy = $row->replicate();
        unset($copy->id, $copy->created_at, $copy->updated_at, $copy->deleted_at);
        foreach (['title', 'name', 'position', 'question', 'customer'] as $col) {
            if (isset($copy->{$col})) {
                $copy->{$col} = $copy->{$col}.' (copy)';
            }
        }
        if (isset($copy->slug)) {
            $copy->slug = Str::slug((string) $copy->slug).'-'.Str::lower(Str::random(4));
        }
        $copy->save();
        $this->audit('duplicate', $copy, $r);

        return back()->with('ok', $def['singular'].' duplicated');
    }

    public function toggle(Request $r, string $resource, $id)
    {
        $def = $this->resource($resource);
        $row = $def['model']::findOrFail($id);
        $row->status = $row->status === 'published' ? 'draft' : 'published';
        $row->save();
        $this->audit('toggle', $row, $r);

        return back()->with('ok', $row->status === 'published' ? 'Published' : 'Unpublished');
    }

    public function reorder(Request $r, string $resource)
    {
        $def = $this->resource($resource);
        $model = $def['model'];
        foreach ((array) $r->input('order', []) as $i => $id) {
            $model::query()->where('id', $id)->update(['sort_order' => $i]);
        }
        $this->audit('reorder', null, $r);

        return response()->json(['ok' => true]);
    }

    protected function payload(Request $r, array $def): array
    {
        $rules = [];
        foreach ($def['fields'] as $name => $field) {
            $rules[$name] = $field['rules'] ?? 'nullable';
        }
        $data = $r->validate($rules);

        foreach (self::ARRAY_FIELDS as $field) {
            if (array_key_exists($field, $data)) {
                $data[$field] = $this->toArray($data[$field]);
            }
        }
        if (array_key_exists('social', $data)) {
            $data['social'] = $this->socialPairs($data['social']);
        }
        if (isset($data['rating']) && $data['rating'] !== null && $data['rating'] !== '') {
            $data['rating'] = (int) $data['rating'];
        }
        if (array_key_exists('project_date', $data) && $data['project_date'] === '') {
            $data['project_date'] = null;
        }
        if (array_key_exists('deadline', $data) && $data['deadline'] === '') {
            $data['deadline'] = null;
        }

        return $data;
    }

    protected function toArray($value): array
    {
        if (is_array($value)) {
            return array_values(array_filter($value, fn ($v) => $v !== null && $v !== ''));
        }
        if ($value === null || $value === '') {
            return [];
        }
        $decoded = json_decode((string) $value, true);
        if (is_array($decoded)) {
            return $decoded;
        }

        return array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', (string) $value))));
    }

    /** "linkedin|https://…" lines or a JSON map become ['linkedin' => 'https://…']. */
    protected function socialPairs($value): array
    {
        if (is_array($value)) {
            return $value;
        }
        $out = [];
        foreach ($this->toArray($value) as $line) {
            if (str_contains($line, '|')) {
                [$net, $url] = array_pad(explode('|', $line, 2), 2, '');
                $net = trim($net);
                if ($net !== '' && trim($url) !== '') {
                    $out[$net] = trim($url);
                }
            }
        }

        return $out;
    }

    protected function saveSeo(Model $row, Request $r): void
    {
        if (! $r->has('seo')) {
            return;
        }
        \App\Models\SeoMeta::updateOrCreate(
            ['seoable_type' => $row::class, 'seoable_id' => $row->getKey()],
            array_intersect_key((array) $r->input('seo'), array_flip([
                'meta_title', 'meta_description', 'canonical', 'robots',
                'og_title', 'og_description', 'og_image', 'twitter_card',
            ]))
        );
    }

    // ------------------------------------------------------------------
    // Gallery albums
    // ------------------------------------------------------------------

    public function albums()
    {
        return view('admin.company.albums', [
            'rows' => GalleryAlbum::withCount('images')->ordered()->get(),
        ]);
    }

    public function saveAlbum(Request $r, $id = null)
    {
        $data = $r->validate([
            'title' => 'required|string|max:190',
            'description' => 'nullable|string',
            'cover' => 'nullable|string|max:255',
            'status' => 'nullable|in:published,draft',
            'sort_order' => 'nullable|integer',
        ]);

        $album = $id ? GalleryAlbum::findOrFail($id) : new GalleryAlbum;
        $album->fill($data)->save();
        $this->audit($id ? 'update' : 'create', $album, $r);

        return back()->with('ok', $id ? 'Album updated' : 'Album created');
    }

    public function destroyAlbum(Request $r, GalleryAlbum $album)
    {
        $album->delete();
        $this->audit('delete', $album, $r);

        return back()->with('ok', 'Album deleted');
    }

    public function albumImages(GalleryAlbum $album)
    {
        return view('admin.company.album-images', [
            'album' => $album,
            'images' => $album->images()->get(),
        ]);
    }

    public function saveImage(Request $r, GalleryAlbum $album)
    {
        $data = $r->validate([
            'path' => 'required|string|max:255',
            'caption' => 'nullable|string|max:255',
            'sort_order' => 'nullable|integer',
        ]);

        $image = GalleryImage::create($data + ['album_id' => $album->id]);
        $this->audit('create', $image, $r);

        return back()->with('ok', 'Image added');
    }

    public function destroyImage(Request $r, GalleryImage $image)
    {
        $image->delete();
        $this->audit('delete', $image, $r);

        return back()->with('ok', 'Image removed');
    }

    public function reorderImages(Request $r, GalleryAlbum $album)
    {
        foreach ((array) $r->input('order', []) as $i => $id) {
            GalleryImage::where('id', $id)->where('album_id', $album->id)->update(['sort_order' => $i]);
        }

        return response()->json(['ok' => true]);
    }
}
