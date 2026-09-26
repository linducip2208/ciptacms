<?php

namespace App\Http\Controllers\Admin;

use App\Models\Webhook;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;

class DeveloperController extends AdminController
{
    /**
     * Events the CMS emits. Modules and plugins can register more by
     * dispatching on demand; this list is what the admin UI offers.
     */
    public const EVENTS = [
        'page.created' => 'Page created',
        'page.updated' => 'Page updated',
        'page.deleted' => 'Page deleted',
        'post.created' => 'Post created',
        'post.published' => 'Post published',
        'comment.created' => 'Comment submitted',
        'form.submitted' => 'Form submitted',
        'contact.message' => 'Contact form message',
        'job.application' => 'Job application received',
        'user.registered' => 'User registered',
        'record.created' => 'Content record created',
        'record.updated' => 'Content record updated',
        'record.deleted' => 'Content record deleted',
    ];

    public function events()
    {
        $registered = [];
        foreach (Event::getRawListeners() as $name => $listeners) {
            $registered[$name] = count($listeners);
        }
        ksort($registered);

        $webhooks = Webhook::orderBy('event')->get();

        return view('admin.developer.events', [
            'catalog' => self::EVENTS,
            'registered' => $registered,
            'webhooks' => $webhooks,
        ]);
    }

    public function dispatchEvent(Request $r)
    {
        $data = $r->validate([
            'event' => 'required|string|max:190',
            'payload' => 'nullable',
        ]);

        $event = $data['event'];
        $payload = [];

        if (! empty($data['payload'])) {
            $decoded = json_decode($data['payload'], true);
            if (! is_array($decoded)) {
                return back()->withErrors(['msg' => 'Payload must be valid JSON object/array.']);
            }
            $payload = $decoded;
        }

        $result = app(\App\Core\Services\WebhookDispatcher::class)->dispatchEvent($event, $payload);
        $workflows = app(\App\Core\Services\WorkflowEngine::class)->trigger($event, $payload);

        $this->audit('dispatch_event', null, $r);

        return back()->with('ok', "Dispatched '{$event}'. Webhooks and workflows were notified.");
    }

    public function hooks()
    {
        $hooks = [];

        foreach (['modules', 'plugins', 'themes'] as $kind) {
            $path = config("lindu.{$kind}_path");
            if (! is_dir($path)) {
                continue;
            }
            foreach (\Illuminate\Support\Facades\File::directories($path) as $dir) {
                $meta = $dir.'/'.($kind === 'themes' ? 'theme' : rtrim($kind, 's')).'.json';
                if (! \Illuminate\Support\Facades\File::exists($meta)) {
                    continue;
                }
                $json = json_decode(\Illuminate\Support\Facades\File::get($meta), true);
                if (! $json) {
                    continue;
                }
                $hooks[] = [
                    'kind' => rtrim($kind, 's'),
                    'slug' => $json['slug'] ?? basename($dir),
                    'name' => $json['name'] ?? basename($dir),
                    'version' => $json['version'] ?? '1.0.0',
                    'active' => (bool) \Illuminate\Support\Facades\DB::table($kind)->where('slug', $json['slug'] ?? '')->value('is_active'),
                    'files' => $this->listExtensionFiles($dir),
                ];
            }
        }

        return view('admin.developer.hooks', ['hooks' => $hooks]);
    }

    protected function listExtensionFiles(string $dir): array
    {
        $out = [];
        $it = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS)
        );
        foreach ($it as $f) {
            if (! $f->isFile()) {
                continue;
            }
            $rel = str_replace('\\', '/', substr($f->getPathname(), strlen($dir) + 1));
            $out[] = $rel;
        }
        sort($out);

        return $out;
    }
}
