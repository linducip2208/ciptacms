<?php

namespace App\Http\Controllers\Admin;

use App\Core\Services\LicenseService;
use App\Models\License;
use Illuminate\Http\Request;

class LicenseController extends AdminController
{
    public function index(Request $r)
    {
        $service = app(LicenseService::class);
        $status = $service->status();

        $q = License::with('activations')->latest();
        if ($search = $r->get('search')) {
            $q->where(function ($w) use ($search) {
                $w->where('license_key', 'like', "%{$search}%")
                    ->orWhere('customer', 'like', "%{$search}%")
                    ->orWhere('product', 'like', "%{$search}%")
                    ->orWhere('domain', 'like', "%{$search}%");
            });
        }
        if ($st = $r->get('status')) {
            $q->where('status', $st);
        }

        return view('admin.license.index', [
            'status' => $status,
            'rows' => $q->paginate(20)->withQueryString(),
            'statuses' => LicenseService::STATUSES,
        ]);
    }

    public function store(Request $r)
    {
        $data = $r->validate([
            'product' => 'required|string|max:190',
            'customer' => 'nullable|string|max:190',
            'domain' => 'nullable|string|max:190',
            'status' => 'nullable|in:'.implode(',', LicenseService::STATUSES),
            'features' => 'nullable',
            'max_activations' => 'nullable|integer|between:1,500',
            'expires_at' => 'nullable|date',
            'support_expires_at' => 'nullable|date',
            'version_entitlement' => 'nullable|string|max:50',
        ]);

        if (! empty($data['domain'])) {
            $data['domain'] = app(LicenseService::class)->normalizeDomain($data['domain']);
        }

        $license = app(LicenseService::class)->issue($data);
        $this->audit('issue_license', $license, $r);

        return back()->with('ok', "License {$license->license_key} issued");
    }

    public function update(Request $r, License $license)
    {
        $data = $r->validate([
            'customer' => 'nullable|string|max:190',
            'domain' => 'nullable|string|max:190',
            'status' => 'required|in:'.implode(',', LicenseService::STATUSES),
            'features' => 'nullable',
            'max_activations' => 'required|integer|between:1,500',
            'expires_at' => 'nullable|date',
            'support_expires_at' => 'nullable|date',
            'version_entitlement' => 'nullable|string|max:50',
        ]);

        if (! empty($data['domain'])) {
            $data['domain'] = app(LicenseService::class)->normalizeDomain($data['domain']);
        }
        if (isset($data['features'])) {
            $decoded = is_array($data['features']) ? $data['features'] : json_decode((string) $data['features'], true);
            $data['features'] = is_array($decoded) ? $decoded : array_filter(array_map('trim', preg_split('/[,\n]/', (string) $data['features'])));
        }

        $license->update($data);
        $this->audit('update_license', $license, $r);

        return back()->with('ok', 'License updated');
    }

    public function destroy(Request $r, License $license)
    {
        $license->delete();
        $this->audit('delete_license', $license, $r);

        return back()->with('ok', 'License deleted');
    }

    public function install(Request $r)
    {
        $data = $r->validate([
            'license_key' => 'required|string|max:190',
            'domain' => 'required|string|max:190',
        ]);

        $result = app(LicenseService::class)->install($data);
        $this->audit('install_license', null, $r);

        return back()->with($result['ok'] ? 'ok' : 'error', $result['message'])
            ->withInput();
    }

    public function uninstall(Request $r)
    {
        app(LicenseService::class)->uninstall();
        $this->audit('uninstall_license', null, $r);

        return back()->with('ok', 'License removed from this installation');
    }

    public function activations(License $license)
    {
        return view('admin.license.activations', [
            'license' => $license,
            'rows' => $license->activations()->orderByDesc('activated_at')->get(),
        ]);
    }

    public function revokeActivation(Request $r, License $license, string $domain)
    {
        $removed = $license->activations()->where('domain', $domain)->delete();
        $this->audit('revoke_activation', $license, $r);

        return back()->with($removed ? 'ok' : 'error', $removed ? "Revoked {$domain}" : 'No such activation');
    }
}
