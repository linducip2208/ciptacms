<?php
namespace App\Core\Services;
use App\Models\License;
use Illuminate\Support\Str;
class LicenseService {
    public function issue(array $d): License {
        return License::create([
            'license_key'=> $d['license_key'] ?? 'LND-'.strtoupper(Str::random(4)).'-'.strtoupper(Str::random(4)).'-'.strtoupper(Str::random(4)),
            'product'=>$d['product'],'customer'=>$d['customer']??null,'domain'=>$d['domain']??null,
            'status'=>$d['status']??'active','features'=>$d['features']??[],'max_activations'=>$d['max_activations']??1,
            'expires_at'=>$d['expires_at']??null,'support_expires_at'=>$d['support_expires_at']??null,'version_entitlement'=>$d['version_entitlement']??null,
        ]);
    }
    public function activate(string $key, string $domain): array {
        $l = License::where('license_key',$key)->first();
        if(!$l) return ['ok'=>false,'message'=>'Invalid license'];
        if(!in_array($l->status,['active'])) return ['ok'=>false,'message'=>'License '.$l->status];
        if($l->expires_at && $l->expires_at->isPast()) { $l->update(['status'=>'expired']); return ['ok'=>false,'message'=>'License expired']; }
        $count = $l->activations()->count();
        if($count >= $l->max_activations && !$l->activations()->where('domain',$domain)->exists()) return ['ok'=>false,'message'=>'Activation limit reached'];
        $l->activations()->updateOrCreate(['domain'=>$domain],['activated_at'=>now(),'ip'=>request()->ip()]);
        $l->update(['last_checked_at'=>now()]);
        return ['ok'=>true,'features'=>$l->features];
    }
}
