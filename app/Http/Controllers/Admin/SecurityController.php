<?php

namespace App\Http\Controllers\Admin;

use App\Core\Services\AuditService;
use App\Core\Services\TwoFactorService;
use App\Models\LoginHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SecurityController extends AdminController
{
    // ==================================================================
    // Two-factor authentication
    // ==================================================================

    public function twoFactor(Request $r, TwoFactorService $tfa)
    {
        $u = $r->user();

        if (! $u->two_factor_secret) {
            $u->update(['two_factor_secret' => $tfa->generateSecret()]);
            $u = $u->fresh();
        }

        $otpauth = $tfa->otpauthUrl($u->email, $u->two_factor_secret);

        $qr = null;
        try {
            $qr = app(\App\Core\Services\QrService::class)->svg($otpauth, 200);
        } catch (\Throwable $e) {
            // QR rendering is optional; the otpauth URL below is enough.
        }

        return view('admin.security.2fa', [
            'user' => $u,
            'otpauth' => $otpauth,
            'qr' => $qr,
        ]);
    }

    public function twoFactorEnable(Request $r, TwoFactorService $tfa)
    {
        $data = $r->validate(['code' => 'required|string']);
        $u = $r->user();

        if (! $tfa->verify($u->two_factor_secret, $data['code'])) {
            return back()->withErrors(['code' => 'That code is not valid.']);
        }

        $u->update([
            'two_factor_enabled' => true,
            'two_factor_backup_codes' => $tfa->backupCodes(),
        ]);

        app(AuditService::class)->log('2fa.enable', $u);

        return back()->with('ok', 'Two-factor authentication is on. Save your backup codes now — they are shown once.');
    }

    public function twoFactorDisable(Request $r)
    {
        $r->validate(['password' => 'required|current_password']);

        $u = $r->user();
        $u->update([
            'two_factor_enabled' => false,
            'two_factor_secret' => null,
            'two_factor_backup_codes' => null,
        ]);

        app(AuditService::class)->log('2fa.disable', $u);

        return back()->with('ok', 'Two-factor authentication is off');
    }

    public function twoFactorRegen(Request $r, TwoFactorService $tfa)
    {
        $r->validate(['password' => 'required|current_password']);

        $u = $r->user();
        $u->update(['two_factor_backup_codes' => $tfa->backupCodes()]);

        app(AuditService::class)->log('2fa.regen_backup_codes', $u);

        return back()->with('ok', 'New backup codes generated. The old ones no longer work.');
    }

    // ==================================================================
    // Sessions
    // ==================================================================

    public function sessions(Request $r)
    {
        $rows = $this->sessionRows($r);

        $hist = $this->safe(fn () => LoginHistory::where('user_id', $r->user()->id)->latest()->limit(20)->get(), collect());

        return view('admin.security.sessions', [
            'rows' => $rows,
            'hist' => $hist,
            'driver' => config('session.driver'),
        ]);
    }

    public function revokeSession(Request $r, string $id)
    {
        $deleted = $this->safe(fn () => DB::table('sessions')->where('id', $id)->where('user_id', $r->user()->id)->delete(), 0);

        $this->audit('revoke_session', null, $r);

        return back()->with($deleted ? 'ok' : 'error', $deleted ? 'Session revoked' : 'Session not found');
    }

    public function revokeOthers(Request $r)
    {
        $current = $r->session()->getId();

        $deleted = $this->safe(fn () => DB::table('sessions')
            ->where('user_id', $r->user()->id)
            ->where('id', '!=', $current)
            ->delete(), 0);

        $this->audit('revoke_other_sessions', null, $r);

        return back()->with('ok', "Signed out of {$deleted} other session(s)");
    }

    // ==================================================================
    // Login history
    // ==================================================================

    public function loginHistory(Request $r)
    {
        $q = LoginHistory::with('user')->latest();

        if ($userId = $r->get('user_id')) {
            $q->where('user_id', $userId);
        }
        if ($search = $r->get('search')) {
            $q->where(function ($w) use ($search) {
                $w->where('email', 'like', "%{$search}%")
                    ->orWhere('ip_address', 'like', "%{$search}%");
            });
        }
        if ($ok = $r->get('successful')) {
            $q->where('successful', $ok === '1');
        }

        $stats = [
            'total' => $this->safe(fn () => LoginHistory::count(), 0),
            'failed' => $this->safe(fn () => LoginHistory::where('successful', false)->count(), 0),
            'today' => $this->safe(fn () => LoginHistory::whereDate('created_at', today())->count(), 0),
        ];

        return view('admin.security.login-history', [
            'rows' => $q->paginate(30)->withQueryString(),
            'stats' => $stats,
            'recentFailures' => $this->safe(
                fn () => LoginHistory::where('successful', false)->latest()->limit(10)->get(),
                collect()
            ),
        ]);
    }

    protected function sessionRows(Request $r)
    {
        if (! $this->safe(fn () => Schema::hasTable('sessions'), false)) {
            return collect();
        }

        return $this->safe(fn () => DB::table('sessions')
            ->where('user_id', $r->user()->id)
            ->orderByDesc('last_activity')
            ->get()
            ->map(function ($s) use ($r) {
                return (object) [
                    'id' => $s->id,
                    'ip' => $s->ip_address,
                    'agent' => $s->user_agent,
                    'payload' => $s->payload,
                    'last_activity' => $s->last_activity ? \Illuminate\Support\Carbon::parse($s->last_activity) : null,
                    'current' => $s->id === $r->session()->getId(),
                ];
            }), collect());
    }
}
