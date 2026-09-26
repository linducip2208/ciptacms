<?php

namespace App\Http\Controllers\Admin;

use App\Models\NotificationDelivery;
use App\Models\NotificationTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class NotificationController extends AdminController
{
    public const CHANNELS = [
        'mail' => 'Email',
        'webhook' => 'Webhook',
        'database' => 'Database',
        'sms' => 'SMS (adapter)',
        'whatsapp' => 'WhatsApp (adapter)',
        'push' => 'Push (adapter)',
    ];

    public function index(Request $r)
    {
        $channel = $r->get('channel');

        $q = NotificationDelivery::with('template')->latest();
        if ($channel) {
            $q->where('channel', $channel);
        }
        if ($status = $r->get('status')) {
            $q->where('status', $status);
        }
        if ($search = $r->get('search')) {
            $q->where(function ($w) use ($search) {
                $w->where('recipient', 'like', "%{$search}%")
                    ->orWhere('subject', 'like', "%{$search}%");
            });
        }

        $byStatus = NotificationDelivery::select('status', \Illuminate\Support\Facades\DB::raw('COUNT(*) as total'))
            ->groupBy('status')->pluck('total', 'status');

        return view('admin.notifications.index', [
            'rows' => $q->paginate(25)->withQueryString(),
            'channels' => self::CHANNELS,
            'byStatus' => $byStatus,
            'channel' => $channel,
        ]);
    }

    public function templates()
    {
        return view('admin.notifications.templates', [
            'rows' => NotificationTemplate::orderBy('channel')->orderBy('slug')->get(),
            'channels' => self::CHANNELS,
        ]);
    }

    public function storeTemplate(Request $r, $template = null)
    {
        $data = $r->validate([
            'name' => 'required|string|max:190',
            'slug' => $template ? 'required|string|max:190' : 'required|string|max:190|unique:notification_templates,slug',
            'channel' => 'required|in:'.implode(',', array_keys(self::CHANNELS)),
            'subject' => 'nullable|string|max:255',
            'body' => 'nullable|string',
            'variables' => 'nullable',
        ]);

        $data['slug'] = \Illuminate\Support\Str::slug($data['slug'], '-');
        $data['variables'] = $this->splitLines($data['variables'] ?? '');

        if ($template) {
            $template->update($data);
            $msg = 'Template updated';
        } else {
            $template = NotificationTemplate::create($data);
            $msg = 'Template created';
        }

        $this->audit($template->wasRecentlyCreated ? 'create' : 'update', $template, $r);

        return back()->with('ok', $msg);
    }

    public function destroyTemplate(Request $r, NotificationTemplate $template)
    {
        $template->delete();
        $this->audit('delete', $template, $r);

        return back()->with('ok', 'Template deleted');
    }

    public function deliveries(Request $r)
    {
        $q = NotificationDelivery::with('template')->latest();
        if ($status = $r->get('status')) {
            $q->where('status', $status);
        }

        return view('admin.notifications.deliveries', [
            'rows' => $q->paginate(30)->withQueryString(),
            'status' => $status,
        ]);
    }

    public function retryDelivery(Request $r, NotificationDelivery $delivery)
    {
        $ok = app(\App\Core\Services\NotificationService::class)->retry($delivery);
        $this->audit('retry_notification', $delivery, $r);

        return back()->with($ok ? 'ok' : 'error', $ok ? 'Notification re-sent' : 'Re-send failed: '.$delivery->error);
    }

    public function sendTest(Request $r)
    {
        $data = $r->validate([
            'channel' => 'required|in:'.implode(',', array_keys(self::CHANNELS)),
            'recipient' => 'required|email',
            'subject' => 'required|string|max:255',
            'body' => 'required|string',
        ]);

        try {
            app(\App\Core\Services\NotificationService::class)->send($data);
            $this->audit('test_notification', null, $r);

            return back()->with('ok', "Test {$data['channel']} notification sent to {$data['recipient']}");
        } catch (\Throwable $e) {
            return back()->withErrors(['msg' => $e->getMessage()]);
        }
    }

    protected function splitLines($value): array
    {
        if (is_array($value)) {
            return $value;
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
}
