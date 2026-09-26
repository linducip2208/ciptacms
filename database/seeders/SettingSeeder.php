<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder; use App\Core\Services\SettingService;
class SettingSeeder extends Seeder {
    public function run(): void {
        $svc = app(SettingService::class);
        $defaults = [
            ['general.site_name','Lindu CMS','text','general',1],['general.tagline','One platform, many applications','text','general',1],
            ['general.locale','id','text','localization',0],['general.timezone','Asia/Jakarta','text','localization',0],
            ['general.currency','IDR','text','localization',0],['general.date_format','d M Y','text','localization',0],
            ['branding.logo','','image','branding',1],['branding.favicon','','image','branding',1],['branding.footer','Powered by Lindu CMS','text','branding',1],
            ['seo.site_name','Lindu CMS','text','seo',1],['seo.meta_description','Lindu CMS - reusable application platform','text','seo',1],
            ['mail.from_address','hello@lindu.local','text','email',0],['storage.default','public','text','storage',0],
            ['security.2fa','0','boolean','security',0],['api.rate_limit','60','number','api',0],
            ['payment.default','manual','text','payment',0],['theme.active','default','text','branding',0],
            ['theme.custom_css','','text','branding',0],['white_label.enabled','0','boolean','branding',0],
        ];
        foreach($defaults as [$k,$v,$t,$g,$pub]){ try{ \App\Models\Setting::firstOrCreate(['key'=>$k],['value'=>$v,'type'=>$t,'group'=>$g,'is_public'=>(bool)$pub]); }catch(\Throwable $e){} }
        $svc->forgetCache();
    }
}
