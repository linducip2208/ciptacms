<?php

namespace App\Http\Controllers\Admin;

use App\Core\Services\BlockLibrary;
use App\Core\Services\DataBuilderService;
use App\Core\Services\WorkflowEngine;
use App\Models\Category;
use App\Models\Comment;
use App\Models\CommentReport;
use App\Models\ContentRecord;
use App\Models\Form;
use App\Models\FormField;
use App\Models\FormSpamSetting;
use App\Models\FormSubmission;
use App\Models\NotificationTemplate;
use App\Models\Page;
use App\Models\PageRevision;
use App\Models\PageTemplate;
use App\Models\Post;
use App\Models\ReusableBlock;
use App\Models\SeoMeta;
use App\Models\WordFilter;
use App\Models\Workflow;
use App\Models\WorkflowRun;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CmsController extends AdminController
{
    // ==================================================================
    // Pages
    // ==================================================================

    public function pages(Request $r)
    {
        $q = Page::with('author')->latest();
        if ($search = $r->get('search')) {
            $q->where('title', 'like', "%{$search}%");
        }
        if ($status = $r->get('status')) {
            $q->where('status', $status);
        }

        return view('admin.cms.pages', [
            'rows' => $q->paginate(20)->withQueryString(),
            'counts' => [
                'all' => Page::count(),
                'published' => Page::where('status', 'published')->count(),
                'draft' => Page::where('status', 'draft')->count(),
            ],
        ]);
    }

    public function pageForm(?Page $page = null)
    {
        return view('admin.cms.page-builder', [
            'row' => $page ?? new Page,
            'templates' => PageTemplate::where('is_active', true)->get(),
            'blocks' => ReusableBlock::where('is_active', true)->get(),
            'componentCatalog' => BlockLibrary::catalog(),
        ]);
    }

    public function pageSave(Request $r, ?string $id = null)
    {
        // A new page costs quota; updating an existing one does not.
        if ($id === null) {
            \App\Core\Services\Quota::guard('pages', 1);
            \App\Core\Services\Quota::assertFeature('page_builder');
        }

        $d = $r->validate([
            'title' => 'required|string|max:190',
            'slug' => 'nullable|string|max:190',
            'excerpt' => 'nullable|string|max:500',
            'body' => 'nullable|string',
            'status' => 'nullable|in:published,draft,scheduled',
            'published_at' => 'nullable|date',
            'template' => 'nullable|string|max:100',
            'is_homepage' => 'nullable|boolean',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
        ]);

        $builder = $this->decodeBuilder($r);

        $row = $id ? Page::findOrFail($id) : new Page;
        if ($row->exists) {
            PageRevision::create([
                'page_id' => $row->id,
                'user_id' => $r->user()?->id,
                'data' => $row->toArray(),
            ]);
        }

        $row->fill(array_merge($d, [
            'slug' => ($d['slug'] ?? null) ?: Str::slug($d['title']).'-'.Str::lower(Str::random(4)),
            'builder' => $builder,
            'is_homepage' => $r->boolean('is_homepage'),
        ]));
        $row->author_id = $row->author_id ?? $r->user()?->id;
        $row->save();

        if ($r->hasAny(['meta_title', 'meta_description', 'canonical', 'robots', 'og_title', 'og_description', 'og_image'])) {
            SeoMeta::updateOrCreate(
                ['seoable_type' => Page::class, 'seoable_id' => $row->id],
                $r->only(['meta_title', 'meta_description', 'canonical', 'robots', 'og_title', 'og_description', 'og_image'])
            );
        }

        $this->audit($id ? 'update' : 'create', $row, $r);

        if ($row->status === 'published') {
            $this->fire('page.updated', ['id' => $row->id, 'title' => $row->title, 'slug' => $row->slug]);
        }

        return redirect()->route('admin.cms.pages.index')->with('ok', $id ? 'Page updated' : 'Page created');
    }

    public function duplicatePage(Request $r, Page $page)
    {
        $copy = $page->replicate();
        unset($copy->id, $copy->created_at, $copy->updated_at);
        $copy->title = $page->title.' (copy)';
        $copy->slug = Str::slug($page->slug).'-'.Str::lower(Str::random(4));
        $copy->status = 'draft';
        $copy->is_homepage = false;
        $copy->save();

        $this->audit('duplicate', $copy, $r);

        return back()->with('ok', 'Page duplicated as a draft');
    }

    public function trashPage(Request $r, Page $page)
    {
        $page->delete();
        $this->audit('trash', $page, $r);

        return back()->with('ok', 'Page moved to trash');
    }

    public function restorePage(Request $r, $id)
    {
        $page = Page::onlyTrashed()->findOrFail($id);
        $page->restore();
        $this->audit('restore', $page, $r);

        return back()->with('ok', 'Page restored');
    }

    public function saveRevision(Request $r, Page $page)
    {
        PageRevision::create([
            'page_id' => $page->id,
            'user_id' => $r->user()?->id,
            'data' => $page->toArray(),
        ]);

        return back()->with('ok', 'Snapshot saved');
    }

    public function previewPage(Page $page)
    {
        $view = $page->is_homepage ? 'site.home' : 'site.page';

        return view($view, [
            'page' => $page,
            'rendered' => $page->builder ? BlockLibrary::render($page->builder) : null,
            'seo' => $this->seo->for(Page::class, $page->id),
            'about' => app(\App\Core\Services\CompanyProfileService::class)->about(),
            'contactInfo' => app(\App\Core\Services\CompanyProfileService::class)->contact(),
        ]);
    }

    // ==================================================================
    // Posts
    // ==================================================================

    public function posts(Request $r)
    {
        $q = Post::with(['category', 'author'])->latest();
        if ($search = $r->get('search')) {
            $q->where('title', 'like', "%{$search}%");
        }
        if ($status = $r->get('status')) {
            $q->where('status', $status);
        }
        if ($cat = $r->get('category_id')) {
            $q->where('category_id', $cat);
        }

        return view('admin.cms.posts', [
            'rows' => $q->paginate(20)->withQueryString(),
            'categories' => Category::orderBy('name')->get(),
        ]);
    }

    public function postForm(?Post $post = null)
    {
        return view('admin.cms.post-form', [
            'row' => $post ?? new Post,
            'categories' => Category::orderBy('name')->get(),
        ]);
    }

    public function postSave(Request $r, ?Post $post = null)
    {
        if ($post === null) {
            \App\Core\Services\Quota::guard('posts', 1);
        }

        $d = $r->validate([
            'title' => 'required|string|max:190',
            'slug' => 'nullable|string|max:190',
            'excerpt' => 'nullable|string|max:500',
            'body' => 'nullable|string',
            'status' => 'nullable|in:published,draft,scheduled',
            'published_at' => 'nullable|date',
            'category_id' => 'nullable|exists:categories,id',
            'featured_image' => 'nullable|string|max:500',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
        ]);

        $row = $post ?? new Post;
        $row->fill(array_merge($d, [
            'slug' => ($d['slug'] ?? null) ?: Str::slug($d['title']).'-'.Str::lower(Str::random(4)),
        ]));
        $row->author_id = $row->author_id ?? $r->user()?->id;
        if ($row->status === 'published' && ! $row->published_at) {
            $row->published_at = now();
        }
        $row->save();

        if ($r->hasAny(['meta_title', 'meta_description', 'canonical', 'robots', 'og_title', 'og_description', 'og_image'])) {
            SeoMeta::updateOrCreate(
                ['seoable_type' => Post::class, 'seoable_id' => $row->id],
                $r->only(['meta_title', 'meta_description', 'canonical', 'robots', 'og_title', 'og_description', 'og_image'])
            );
        }

        $this->audit($post ? 'update' : 'create', $row, $r);
        $this->fire('post.created', ['id' => $row->id, 'title' => $row->title, 'slug' => $row->slug]);

        return redirect()->route('admin.cms.posts.index')->with('ok', $post ? 'Post updated' : 'Post created');
    }

    public function trashPost(Request $r, Post $post)
    {
        $post->delete();
        $this->audit('trash', $post, $r);

        return back()->with('ok', 'Post moved to trash');
    }

    public function restorePost(Request $r, $id)
    {
        $post = Post::onlyTrashed()->findOrFail($id);
        $post->restore();
        $this->audit('restore', $post, $r);

        return back()->with('ok', 'Post restored');
    }

    // ==================================================================
    // Revisions & trash
    // ==================================================================

    public function revisions(Request $r)
    {
        $q = PageRevision::with('user')->latest();
        if ($pageId = $r->get('page_id')) {
            $q->where('page_id', $pageId);
        }

        return view('admin.cms.revisions', [
            'rows' => $q->paginate(25)->withQueryString(),
            'pages' => Page::orderBy('title')->get(['id', 'title']),
        ]);
    }

    public function restoreRevision(Request $r, PageRevision $revision)
    {
        $page = Page::findOrFail($revision->page_id);

        // Snapshot the current state first so the restore itself is undoable.
        PageRevision::create([
            'page_id' => $page->id,
            'user_id' => $r->user()?->id,
            'data' => $page->toArray(),
        ]);

        $data = (array) $revision->data;
        foreach (['title', 'slug', 'excerpt', 'body', 'builder', 'status', 'published_at', 'template'] as $key) {
            if (array_key_exists($key, $data)) {
                $page->{$key} = $data[$key];
            }
        }
        $page->save();
        $this->audit('restore_revision', $page, $r);

        return back()->with('ok', "Restored the snapshot from {$revision->created_at->diffForHumans()}");
    }

    public function destroyRevision(Request $r, PageRevision $revision)
    {
        $revision->delete();
        $this->audit('delete_revision', null, $r);

        return back()->with('ok', 'Snapshot deleted');
    }

    /** Whitelist: trash can only be emptied for these models. */
    public const TRASHABLE = [
        'pages' => Page::class,
        'posts' => Post::class,
    ];

    public function trash(Request $r)
    {
        $rows = [];
        foreach (self::TRASHABLE as $type => $class) {
            $rows[$type] = $class::onlyTrashed()->latest()->limit(50)->get();
        }

        return view('admin.cms.trash', ['rows' => $rows]);
    }

    public function emptyTrash(Request $r, string $type, $id)
    {
        abort_unless(isset(self::TRASHABLE[$type]), 404);
        $row = self::TRASHABLE[$type]::onlyTrashed()->findOrFail($id);
        $row->forceDelete();
        $this->audit('force_delete', $row, $r);

        return back()->with('ok', 'Permanently deleted');
    }

    public function forceDelete(Request $r, string $type, $id)
    {
        return $this->emptyTrash($r, $type, $id);
    }

    // ==================================================================
    // Page builder resources
    // ==================================================================

    public function sections()
    {
        return view('admin.cms.sections', [
            'catalog' => BlockLibrary::sections(),
        ]);
    }

    public function components()
    {
        return view('admin.cms.components', [
            'catalog' => BlockLibrary::catalog(),
        ]);
    }

    public function blocks()
    {
        return view('admin.cms.blocks', [
            'rows' => ReusableBlock::orderBy('name')->get(),
            'catalog' => BlockLibrary::catalog(),
        ]);
    }

    public function saveBlock(Request $r, ?ReusableBlock $block = null)
    {
        $d = $r->validate([
            'name' => 'required|string|max:190',
            'slug' => 'nullable|string|max:190',
            'type' => 'required|string|max:100',
            'data' => 'nullable',
            'is_global' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
        ]);

        $data = $d['data'] ?? [];
        if (is_string($data)) {
            $decoded = json_decode($data, true);
            $data = is_array($decoded) ? $decoded : ['body' => $data];
        }

        // There is no `type` column: the component type is part of the
        // payload so a saved block round-trips through the builder intact.
        $data['type'] = $d['type'];

        $row = $block ?? new ReusableBlock;
        $row->fill([
            'name' => $d['name'],
            'slug' => ($d['slug'] ?? null) ?: Str::slug($d['name']),
            'data' => $data,
            'is_global' => $r->boolean('is_global'),
            'is_active' => $r->boolean('is_active', true),
        ])->save();

        $this->audit($block ? 'update' : 'create', $row, $r);

        return back()->with('ok', $block ? 'Block updated' : 'Block saved');
    }

    public function destroyBlock(Request $r, ReusableBlock $block)
    {
        $block->delete();
        $this->audit('delete', $block, $r);

        return back()->with('ok', 'Block deleted');
    }

    public function templates()
    {
        return view('admin.cms.templates', [
            'rows' => PageTemplate::orderBy('name')->get(),
        ]);
    }

    public function saveTemplate(Request $r, ?PageTemplate $pageTemplate = null)
    {
        $d = $r->validate([
            'name' => 'required|string|max:190',
            'slug' => 'nullable|string|max:190',
            'description' => 'nullable|string|max:500',
            'thumbnail' => 'nullable|string|max:500',
            'structure' => 'nullable',
            'is_active' => 'nullable|boolean',
        ]);

        $structure = $d['structure'] ?? [];
        if (is_string($structure)) {
            $structure = json_decode($structure, true) ?: [];
        }

        $row = $pageTemplate ?? new PageTemplate;
        $row->fill([
            'name' => $d['name'],
            'slug' => ($d['slug'] ?? null) ?: Str::slug($d['name']),
            'description' => $d['description'] ?? null,
            'thumbnail' => $d['thumbnail'] ?? null,
            'structure' => $structure ?: ['sections' => []],
            'is_active' => $r->boolean('is_active', true),
        ])->save();

        $this->audit($pageTemplate ? 'update' : 'create', $row, $r);

        return back()->with('ok', $pageTemplate ? 'Template updated' : 'Template saved');
    }

    public function destroyTemplate(Request $r, PageTemplate $template)
    {
        $template->delete();
        $this->audit('delete', $template, $r);

        return back()->with('ok', 'Template deleted');
    }

    public function applyTemplate(Request $r, PageTemplate $template)
    {
        $page = $r->validate(['page_id' => 'required|exists:pages,id'])['page_id'];
        $target = Page::findOrFail($page);

        PageRevision::create([
            'page_id' => $target->id,
            'user_id' => $r->user()?->id,
            'data' => $target->toArray(),
        ]);

        $target->update(['builder' => $template->structure()]);
        $this->audit('apply_template', $target, $r);

        return back()->with('ok', "Template '{$template->name}' applied. The previous layout is saved in Revisions.");
    }

    // ==================================================================
    // Comments
    // ==================================================================

    public function comments(Request $r)
    {
        $q = Comment::with('post')->latest();
        if ($status = $r->get('status')) {
            $q->where('status', $status);
        }
        if ($search = $r->get('search')) {
            $q->where(function ($w) use ($search) {
                $w->where('author_name', 'like', "%{$search}%")
                    ->orWhere('author_email', 'like', "%{$search}%")
                    ->orWhere('body', 'like', "%{$search}%");
            });
        }

        return view('admin.cms.comments', [
            'rows' => $q->paginate(25)->withQueryString(),
            'counts' => [
                'all' => Comment::count(),
                'pending' => Comment::where('status', 'pending')->count(),
                'approved' => Comment::where('status', 'approved')->count(),
                'spam' => Comment::where('status', 'spam')->count(),
                'trash' => Comment::onlyTrashed()->count(),
            ],
        ]);
    }

    public function moderate(Request $r, Comment $comment, string $status)
    {
        abort_unless(in_array($status, ['pending', 'approved', 'spam', 'trash'], true), 404);

        if ($status === 'trash') {
            $comment->delete();
        } else {
            $comment->update(['status' => $status]);
        }

        $this->audit('moderate_comment', $comment, $r);

        return back()->with('ok', 'Comment moved to '.$status);
    }

    public function bulkComments(Request $r)
    {
        $d = $r->validate([
            'action' => 'required|in:approve,spam,trash',
            'ids' => 'required|array',
            'ids.*' => 'integer',
        ]);

        $q = Comment::whereIn('id', $d['ids']);
        $count = match ($d['action']) {
            'approve' => $q->update(['status' => 'approved']),
            'spam' => $q->update(['status' => 'spam']),
            'trash' => $q->delete(),
        };

        $this->audit('bulk_'.$d['action'].'_comments', null, $r);

        return back()->with('ok', "{$count} comment(s) updated");
    }

    public function wordFilter()
    {
        return view('admin.cms.word-filter', [
            'rows' => WordFilter::orderBy('word')->get(),
        ]);
    }

    public function saveWordFilter(Request $r)
    {
        $d = $r->validate([
            'word' => 'required|string|max:120',
            'action' => 'required|in:spam,reject,hold',
        ]);

        $word = WordFilter::updateOrCreate(
            ['word' => mb_strtolower(trim($d['word']))],
            ['action' => $d['action'], 'is_active' => true]
        );
        $this->audit('upsert_word_filter', $word, $r);

        return back()->with('ok', "Word '{$word->word}' will be {$word->action}ed on new comments");
    }

    public function destroyWordFilter(Request $r, WordFilter $wordFilter)
    {
        $wordFilter->delete();
        $this->audit('delete_word_filter', null, $r);

        return back()->with('ok', 'Word removed from the filter');
    }

    public function commentReports()
    {
        return view('admin.cms.comment-reports', [
            'rows' => CommentReport::with('comment')->latest()->paginate(30),
            'openCount' => CommentReport::open()->count(),
        ]);
    }

    public function updateCommentReport(Request $r, CommentReport $report)
    {
        $report->update($r->validate(['status' => 'required|in:open,resolved,ignored'])['status']);
        $this->audit('update_comment_report', null, $r);

        return back()->with('ok', 'Report updated');
    }

    // ==================================================================
    // Form builder
    // ==================================================================

    public function forms(Request $r)
    {
        $q = Form::withCount(['fields', 'submissions'])->latest();
        if ($search = $r->get('search')) {
            $q->where('title', 'like', "%{$search}%");
        }

        return view('admin.cms.forms', ['rows' => $q->paginate(20)->withQueryString()]);
    }

    public function saveForm(Request $r, ?Form $form = null)
    {
        $d = $r->validate([
            // The table column is `name`; `title` is accepted as an alias so
            // the documented form payload works either way.
            'name' => 'nullable|string|max:190',
            'title' => 'nullable|string|max:190',
            'slug' => 'nullable|string|max:190',
            'description' => 'nullable|string|max:1000',
            'submit_label' => 'nullable|string|max:100',
            'success_message' => 'nullable|string|max:1000',
            'status' => 'nullable|in:published,draft',
        ]);

        $name = $d['name'] ?? $d['title'] ?? null;
        if (! $name) {
            return back()->withErrors(['name' => 'A form needs a name.'])->withInput();
        }

        $row = $form ?? new Form;
        $row->fill([
            'name' => $name,
            'slug' => ($d['slug'] ?? null) ?: Str::slug($name).'-'.Str::lower(Str::random(4)),
            'description' => $d['description'] ?? null,
            'submit_label' => ($d['submit_label'] ?? null) ?: 'Submit',
            // submit_text predates submit_label and is still on the table.
            'submit_text' => ($d['submit_label'] ?? null) ?: 'Submit',
            'success_message' => ($d['success_message'] ?? null) ?: 'Thank you. Your message has been received.',
            'status' => ($d['status'] ?? null) ?: 'published',
            'is_active' => (($d['status'] ?? null) ?: 'published') === 'published',
        ])->save();

        $this->audit($form ? 'update' : 'create', $row, $r);

        return redirect()->route('admin.cms.forms.builder', $row)->with('ok', $form ? 'Form updated' : 'Form created — add fields next');
    }

    public function destroyForm(Request $r, Form $form)
    {
        if ($form->submissions()->exists()) {
            return back()->withErrors(['msg' => 'This form has submissions. Delete them first.']);
        }

        // Hard delete: the guard above already guarantees nothing references
        // this form, so a soft-delete tombstone would only hide a form the
        // operator believes they removed.
        $form->fields()->delete();
        $form->forceDelete();
        $this->audit('delete', $form, $r);

        return redirect()->route('admin.cms.forms.index')->with('ok', 'Form deleted');
    }

    public function formBuilder(Form $form)
    {
        return view('admin.cms.form-builder', [
            'form' => $form->load(['fields', 'submissions']),
            'fieldTypes' => FormField::TYPES,
        ]);
    }

    public function saveField(Request $r, Form $form, ?FormField $field = null)
    {
        abort_if($field && $field->form_id !== $form->id, 404);

        $d = $r->validate([
            'label' => 'required|string|max:190',
            'name' => 'nullable|string|max:100',
            'type' => 'required|in:'.implode(',', array_keys(FormField::TYPES)),
            'placeholder' => 'nullable|string|max:190',
            'help' => 'nullable|string|max:500',
            'options' => 'nullable',
            'is_required' => 'nullable|boolean',
            'is_unique' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
        ]);

        $row = $field ?? new FormField;
        $row->form_id = $form->id;
        $row->fill([
            'label' => $d['label'],
            // `name` is optional in the form; derive it from the label when absent.
            'name' => ($d['name'] ?? null) ?: Str::snake(str_replace(' ', '_', $d['label'])),
            'type' => $d['type'],
            'placeholder' => $d['placeholder'] ?? null,
            'help' => $d['help'] ?? null,
            'options' => $this->toList($d['options'] ?? null),
            'is_required' => $r->boolean('is_required'),
            'is_unique' => $r->boolean('is_unique'),
            'is_active' => $r->boolean('is_active', true),
            'sort_order' => $row->exists ? $row->sort_order : (int) $form->fields()->max('sort_order') + 1,
        ])->save();

        $this->audit($field ? 'update' : 'create', $row, $r);

        return back()->with('ok', $field ? 'Field updated' : 'Field added');
    }

    public function destroyField(Request $r, Form $form, FormField $field)
    {
        abort_unless($field->form_id === $form->id, 404);
        $field->delete();
        $this->audit('delete', $field, $r);

        return back()->with('ok', 'Field removed');
    }

    public function reorderFields(Request $r, Form $form)
    {
        foreach ((array) $r->input('order', []) as $i => $id) {
            FormField::where('id', $id)->where('form_id', $form->id)->update(['sort_order' => $i]);
        }

        return response()->json(['ok' => true]);
    }

    public function formFields()
    {
        return view('admin.cms.form-fields', [
            'rows' => FormField::with('form')->orderBy('form_id')->orderBy('sort_order')->get(),
            'types' => FormField::TYPES,
            'forms' => Form::orderBy('title')->get(),
        ]);
    }

    public function submissions(Request $r)
    {
        $q = FormSubmission::with('form')->latest();
        if ($formId = $r->get('form_id')) {
            $q->where('form_id', $formId);
        }
        if ($search = $r->get('search')) {
            $q->where('data', 'like', "%{$search}%");
        }

        return view('admin.cms.submissions', [
            'rows' => $q->paginate(25)->withQueryString(),
            'forms' => Form::orderBy('title')->get(),
        ]);
    }

    public function showSubmission(FormSubmission $submission)
    {
        return view('admin.cms.submission', ['submission' => $submission]);
    }

    public function destroySubmission(Request $r, FormSubmission $submission)
    {
        $submission->delete();
        $this->audit('delete', $submission, $r);

        return back()->with('ok', 'Submission deleted');
    }

    public function exportSubmissions(Request $r)
    {
        $q = FormSubmission::with('form')->latest();
        if ($formId = $r->get('form_id')) {
            $q->where('form_id', $formId);
        }

        $rows = $q->limit(5000)->get()->map(fn ($s) => [
            'id' => $s->id,
            'form' => $s->form?->title,
            'data' => json_encode($s->data, JSON_UNESCAPED_UNICODE),
            'ip' => $s->ip,
            'created_at' => $s->created_at?->toIso8601String(),
        ])->all();

        if (($r->get('format') ?: 'csv') === 'json') {
            return response()->json(['count' => count($rows), 'data' => $rows])
                ->header('Content-Disposition', 'attachment; filename="submissions.json"');
        }

        $csv = app(\App\Core\Services\ImportExportService::class)->toCsv($rows);

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="submissions.csv"',
        ]);
    }

    public function formNotifications()
    {
        return view('admin.cms.form-notifications', [
            'rows' => NotificationTemplate::where('channel', 'mail')
                ->whereIn('slug', ['form.notification', 'form.admin_notification', 'contact.message', 'job.application'])
                ->orWhere('slug', 'like', 'form.%')
                ->get(),
            'channels' => ['mail' => 'Email', 'webhook' => 'Webhook', 'database' => 'Database'],
        ]);
    }

    public function saveFormNotification(Request $r, $template = null)
    {
        $d = $r->validate([
            'name' => 'required|string|max:190',
            'slug' => $template ? 'required|string|max:190' : 'required|string|max:190|unique:notification_templates,slug',
            'channel' => 'required|in:mail,webhook,database',
            'subject' => 'nullable|string|max:255',
            'body' => 'nullable|string',
            'variables' => 'nullable',
        ]);

        $d['slug'] = Str::slug($d['slug'], '-');
        $d['variables'] = $this->toList($d['variables'] ?? null);

        $row = $template ?? new NotificationTemplate;
        $row->fill($d)->save();

        $this->audit($template ? 'update' : 'create', $row, $r);

        return back()->with('ok', $template ? 'Notification updated' : 'Notification created');
    }

    public function destroyFormNotification(Request $r, NotificationTemplate $template)
    {
        $template->delete();
        $this->audit('delete', $template, $r);

        return back()->with('ok', 'Notification deleted');
    }

    public function formSpam()
    {
        $row = FormSpamSetting::query()->latest('id')->first() ?? new FormSpamSetting;

        return view('admin.cms.form-spam', ['settings' => $row]);
    }

    public function saveFormSpam(Request $r)
    {
        $d = $r->validate([
            'honeypot' => 'nullable|boolean',
            'rate_limit_per_minute' => 'required|integer|between:1,120',
            'min_fill_seconds' => 'required|integer|between:0,60',
            'block_disposable_email' => 'nullable|boolean',
            'captcha' => 'nullable|boolean',
            'blocked_words' => 'nullable',
        ]);

        $row = FormSpamSetting::query()->latest('id')->first() ?? new FormSpamSetting;
        $row->fill([
            'honeypot' => $r->boolean('honeypot', true),
            'rate_limit_per_minute' => (int) $d['rate_limit_per_minute'],
            'min_fill_seconds' => (int) $d['min_fill_seconds'],
            'block_disposable_email' => $r->boolean('block_disposable_email'),
            'captcha' => $r->boolean('captcha'),
            'blocked_words' => $this->toList($d['blocked_words'] ?? null),
        ])->save();

        $this->audit('update_form_spam', $row, $r);

        return back()->with('ok', 'Spam protection saved');
    }

    // ==================================================================
    // Workflows
    // ==================================================================

    public function workflows(Request $r)
    {
        $q = Workflow::withCount('runs')->latest();
        if ($search = $r->get('search')) {
            $q->where('name', 'like', "%{$search}%");
        }
        if ($active = $r->get('is_active')) {
            $q->where('is_active', $active === '1');
        }

        return view('admin.cms.workflows', [
            'rows' => $q->paginate(20)->withQueryString(),
            'triggers' => WorkflowEngine::TRIGGERS,
            'conditions' => WorkflowEngine::CONDITIONS,
            'actions' => WorkflowEngine::ACTIONS,
        ]);
    }

    public function workflowForm(?Workflow $workflow = null)
    {
        return view('admin.cms.workflow-form', [
            'row' => $workflow ?? new Workflow,
            'triggers' => WorkflowEngine::TRIGGERS,
            'conditions' => WorkflowEngine::CONDITIONS,
            'actions' => WorkflowEngine::ACTIONS,
        ]);
    }

    public function workflowSave(Request $r, Workflow $workflow = null)
    {
        $d = $r->validate([
            'name' => 'required|string|max:190',
            'trigger_event' => 'required|string|max:190',
            'conditions' => 'nullable',
            'actions' => 'nullable',
            'is_active' => 'nullable|boolean',
        ]);

        $row = $workflow ?? new Workflow;
        $row->fill([
            'name' => $d['name'],
            'trigger_event' => $d['trigger_event'],
            'conditions' => $this->decodeJsonArray($r->input('conditions')),
            'actions' => $this->decodeJsonArray($r->input('actions')),
            'is_active' => $r->boolean('is_active', true),
        ])->save();

        $this->audit($workflow ? 'update' : 'create', $row, $r);

        return redirect()->route('admin.cms.workflows.index')->with('ok', $workflow ? 'Workflow updated' : 'Workflow created');
    }

    public function destroyWorkflow(Request $r, Workflow $workflow)
    {
        $workflow->delete();
        $this->audit('delete', $workflow, $r);

        return back()->with('ok', 'Workflow deleted');
    }

    public function workflowTriggers()
    {
        return view('admin.cms.workflow-reference', [
            'title' => 'Triggers',
            'description' => 'Events that start a workflow. A workflow only runs when its trigger matches and it is active.',
            'items' => collect(WorkflowEngine::TRIGGERS)->map(fn ($l, $k) => ['key' => $k, 'label' => $l])->values(),
            'back' => route('admin.cms.workflows.index'),
        ]);
    }

    public function workflowConditions()
    {
        return view('admin.cms.workflow-reference', [
            'title' => 'Conditions',
            'description' => 'Every condition in a step must pass. An empty condition list means "always".',
            'items' => collect(WorkflowEngine::CONDITIONS)->map(fn ($l, $k) => ['key' => $k, 'label' => $l])->values(),
            'back' => route('admin.cms.workflows.index'),
        ]);
    }

    public function workflowActions()
    {
        return view('admin.cms.workflow-reference', [
            'title' => 'Actions',
            'description' => 'What happens when the conditions pass. Actions run in order.',
            'items' => collect(WorkflowEngine::ACTIONS)->map(fn ($l, $k) => ['key' => $k, 'label' => $l])->values(),
            'back' => route('admin.cms.workflows.index'),
        ]);
    }

    public function workflowRuns(Request $r)
    {
        $q = WorkflowRun::with('workflow')->latest();
        if ($status = $r->get('status')) {
            $q->where('status', $status);
        }
        if ($workflowId = $r->get('workflow_id')) {
            $q->where('workflow_id', $workflowId);
        }

        return view('admin.cms.workflow-runs', [
            'rows' => $q->paginate(30)->withQueryString(),
            'workflows' => Workflow::orderBy('name')->get(),
            'status' => $status,
        ]);
    }

    public function showWorkflowRun(WorkflowRun $run)
    {
        return view('admin.cms.workflow-run', ['run' => $run]);
    }

    public function retryWorkflowRun(Request $r, WorkflowRun $run)
    {
        app(WorkflowEngine::class)->retry($run);
        $this->audit('retry_workflow_run', null, $r);

        return back()->with('ok', 'Run re-queued');
    }

    // ==================================================================
    // Legacy redirects (superseded by the dedicated controllers)
    // ==================================================================

    public function webhooks()
    {
        return redirect()->route('admin.cms.webhooks.index');
    }

    public function seo()
    {
        return redirect()->route('admin.seo.index');
    }

    // ==================================================================
    // Helpers
    // ==================================================================

    protected function decodeBuilder(Request $r): ?array
    {
        $builder = $r->input('builder');

        if (is_array($builder)) {
            return $builder;
        }
        if (is_string($builder) && trim($builder) !== '') {
            $decoded = json_decode($builder, true);
            if (is_array($decoded)) {
                return $decoded;
            }

            throw \Illuminate\Validation\ValidationException::withMessages([
                'builder' => 'The page layout is not valid JSON.',
            ]);
        }

        return $r->input('builder_array');
    }

    protected function decodeJsonArray($value): array
    {
        if (is_array($value)) {
            return $value;
        }
        if (! $value) {
            return [];
        }
        $decoded = json_decode((string) $value, true);

        return is_array($decoded) ? $decoded : [];
    }

    protected function toList($value): array
    {
        if (is_array($value)) {
            return array_values(array_filter($value, fn ($v) => $v !== null && $v !== ''));
        }
        if (! $value) {
            return [];
        }
        $decoded = json_decode((string) $value, true);
        if (is_array($decoded)) {
            return $decoded;
        }

        return array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', (string) $value))));
    }

    protected function fire(string $event, array $payload): void
    {
        try {
            event('cms.'.$event, $payload);
            app(\App\Core\Services\WebhookDispatcher::class)->dispatchEvent($event, $payload);
            app(WorkflowEngine::class)->trigger($event, $payload);
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
