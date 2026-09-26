<?php

namespace App\Http\Controllers\Admin;

use App\Core\Services\LicenseService;
use App\Models\License;
use App\Models\Plan;
use App\Models\PlanFeature;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\TenantUsage;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * SaaS readiness surface. Deliberately *not* a billing application:
 * tenants, plans, feature flags, quotas and a payment-adapter seam.
 */
class SaasController extends AdminController
{
    public function tenants()
    {
        return view('admin.saas.tenants', [
            'tenants' => Tenant::with('plan')->paginate(20),
            'plans' => Plan::where('is_active', true)->orderBy('sort_order')->get(),
        ]);
    }

    public function storeTenant(Request $r)
    {
        $data = $r->validate([
            'name' => 'required|string|max:190',
            'plan_id' => 'nullable|exists:plans,id',
            'domain' => 'nullable|string|max:190',
        ]);

        $id = (string) Str::uuid();
        $tenant = Tenant::create([
            'id' => $id,
            'uuid' => $id,
            'name' => $data['name'],
            'slug' => Str::slug($data['name']).'-'.substr($id, 0, 4),
            'plan_id' => $data['plan_id'] ?? null,
            'status' => 'active',
        ]);

        if (! empty($data['domain'])) {
            \App\Models\WhiteLabelDomain::create([
                'tenant_id' => $tenant->id,
                'domain' => $data['domain'],
                'is_primary' => true,
                'is_verified' => false,
            ]);
        }

        $this->audit('create_tenant', $tenant, $r);

        return back()->with('ok', "Tenant '{$tenant->name}' created");
    }

    public function updateTenant(Request $r, Tenant $tenant)
    {
        $data = $r->validate([
            'name' => 'required|string|max:190',
            'plan_id' => 'nullable|exists:plans,id',
            'status' => 'required|in:active,suspended,trial',
        ]);

        $tenant->update($data);
        $this->audit('update_tenant', $tenant, $r);

        return back()->with('ok', 'Tenant updated');
    }

    public function plans()
    {
        return view('admin.saas.plans', [
            'plans' => Plan::with('features')->orderBy('sort_order')->get(),
        ]);
    }

    public function storePlan(Request $r)
    {
        $data = $r->validate([
            'name' => 'required|string|max:190',
            'slug' => 'required|string|max:190|unique:plans,slug',
            'price' => 'nullable|numeric',
            'billing_period' => 'nullable|in:monthly,yearly,one-time',
            'limits' => 'nullable',
        ]);

        Plan::create([
            'name' => $data['name'],
            'slug' => Str::slug($data['slug'], '-'),
            'price' => $data['price'] ?? 0,
            'billing_period' => $data['billing_period'] ?? 'monthly',
            'limits' => is_array($data['limits'] ?? null) ? $data['limits'] : json_decode((string) ($data['limits'] ?? ''), true),
            'is_active' => true,
        ]);

        $this->audit('create_plan', null, $r);

        return back()->with('ok', 'Plan created');
    }

    public function features()
    {
        return view('admin.saas.features', [
            'plans' => Plan::orderBy('sort_order')->get(),
            'features' => PlanFeature::orderBy('plan_id')->orderBy('key')->get()->groupBy('plan_id'),
        ]);
    }

    public function subscriptions()
    {
        return view('admin.saas.subscriptions', [
            'rows' => Subscription::with('tenant', 'plan')->latest()->paginate(20),
        ]);
    }

    public function usage()
    {
        $rows = TenantUsage::orderByDesc('period')->orderBy('tenant_id')->get()->groupBy('tenant_id');

        return view('admin.saas.usage', [
            'rows' => $rows,
            'period' => now()->format('Y-m'),
        ]);
    }

    public function domains()
    {
        return view('admin.saas.domains', [
            'rows' => \App\Models\WhiteLabelDomain::orderBy('domain')->get(),
        ]);
    }

    public function billing()
    {
        $adapters = config('lindu.payments.adapters', []);

        return view('admin.saas.billing', [
            'gateways' => (array) setting('billing.gateways', []),
            'adapters' => $adapters,
        ]);
    }

    public function gateways()
    {
        $adapters = config('lindu.payments.adapters', []);

        return view('admin.saas.gateways', [
            'gateways' => (array) setting('billing.gateways', []),
            'adapters' => $adapters,
        ]);
    }

    public function saveGateways(Request $r)
    {
        $adapters = array_keys((array) config('lindu.payments.adapters', []));
        $data = $r->validate([
            'gateway' => 'nullable|in:'.implode(',', $adapters ?: ['none']),
        ]);

        $chosen = $data['gateway'] ?? null;
        $settings = app(\App\Core\Services\SettingService::class);

        if ($chosen) {
            $r->validate(['secret_key' => 'nullable|string|max:255']);
            $existing = (array) setting('billing.gateways', []);
            $existing[$chosen] = array_merge($existing[$chosen] ?? [], array_filter([
                'enabled' => true,
                'secret_key' => $r->input('secret_key') ?: ($existing[$chosen]['secret_key'] ?? null),
                'mode' => $r->input('mode', $existing[$chosen]['mode'] ?? 'live'),
            ]));
            $settings->set('billing.gateways', json_encode($existing), 'json', 'billing');
        } else {
            $settings->set('billing.gateways', json_encode([]), 'json', 'billing');
        }

        $this->audit('update_payment_gateways', null, $r);

        return back()->with('ok', 'Payment configuration saved');
    }
}
