<?php
namespace App\Core\Services;
use App\Models\SeoMeta;
class SeoService {
    public function for(string $type, string $id): array {
        $row = SeoMeta::where('seoable_type',$type)->where('seoable_id',$id)->first();
        return $row?->toArray() ?? [];
    }
    public function render(?array $meta=null): string {
        $m = array_merge(['title'=>setting('seo.site_name','Lindu CMS'),'description'=>setting('seo.meta_description',''),'canonical'=>url()->current(),'robots'=>'index,follow'], (array)$meta);
        $e = fn($v)=>htmlspecialchars((string)$v, ENT_QUOTES);
        $h = "<title>{$e($m['title'])}</title>\n<meta name=\"description\" content=\"{$e($m['description'])}\">\n";
        $h .= "<link rel=\"canonical\" href=\"{$e($m['canonical'])}\">\n<meta name=\"robots\" content=\"{$e($m['robots'])}\">\n";
        if(!empty($m['og_title'])) $h .= "<meta property=\"og:title\" content=\"{$e($m['og_title'])}\">\n";
        if(!empty($m['og_description'])) $h .= "<meta property=\"og:description\" content=\"{$e($m['og_description'])}\">\n";
        if(!empty($m['og_image'])) $h .= "<meta property=\"og:image\" content=\"{$e($m['og_image'])}\">\n";
        if(!empty($m['twitter_card'])) $h .= "<meta name=\"twitter:card\" content=\"{$e($m['twitter_card'])}\">\n";
        if(!empty($m['schema'])) $h .= "<script type=\"application/ld+json\">".$m['schema']."</script>\n";
        return $h;
    }
    public function sitemap(): string {
        $urls = [url('/'), url('/blog')];
        try {
            foreach(\App\Models\Page::where('status','published')->get() as $p) $urls[] = url('/p/'.$p->slug);
            foreach(\App\Models\Post::where('status','published')->get() as $p) $urls[] = url('/blog/'.$p->slug);
        } catch(\Throwable $e){}
        $xml = '<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
        foreach($urls as $u) $xml .= '<url><loc>'.htmlspecialchars($u).'</loc></url>';
        return $xml.'</urlset>';
    }
}
