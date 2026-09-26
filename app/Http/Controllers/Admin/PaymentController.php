<?php
namespace App\Http\Controllers\Admin;
use Illuminate\Http\Request;
class PaymentController extends AdminController {
    public function gateways(){ $adapters=['xendit','ipaymu','tripay','stripe','manual']; $current=setting('payment.default','manual'); return view('admin.payments.gateways',compact('adapters','current')); }
    public function save(Request $r){
        $svc=app(\App\Core\Services\SettingService::class);
        $svc->set('payment.default',$r->get('gateway','manual'),'text','payment');
        foreach(['xendit','ipaymu','tripay','stripe'] as $g){
            foreach(['key','secret','mode'] as $f){ if($r->filled("{$g}_{$f}")) $svc->set("payment.{$g}.{$f}",$r->get("{$g}_{$f}"), $f==='secret'?'secret':'text','payment'); }
        }
        return back()->with('ok','Payment settings saved');
    }
    public function test(Request $r){
        $gw=app(\App\Payments\PaymentManager::class)->driver($r->get('gateway'));
        $res=$gw->charge(['number'=>'TEST-001'],(float)$r->get('amount',10000));
        return back()->with('ok','Test ['.$gw->name().']: '.json_encode($res));
    }
}
