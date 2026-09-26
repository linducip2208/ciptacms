<?php

namespace App\Core\Services;

use App\Models\Cp\Career;
use App\Models\Cp\Faq;
use App\Models\Cp\GalleryAlbum;
use App\Models\Cp\Portfolio;
use App\Models\Cp\Product;
use App\Models\Cp\Service;
use App\Models\Page;
use App\Models\Post;
use App\Models\SeoMeta;

class SeoService
{
    /** @var array<int,array{loc:string,lastmod:?string,changefreq:string,priority:string}> */
    protected array $entries = [];

    public function for(string $type, string $id): array
    {
        $row = SeoMeta::where('seoable_type', $type)->where('seoable_id', $id)->first();

        return $row?->toArray() ?? [];
    }

    /** Resolve the effective SEO title for a model, falling back to its own title. */
    public function titleFor($model, string $fallback = ''): string
    {
        try {
            $meta = $this->for($model::class, $model->getKey());
            if (! empty($meta['meta_title'])) {
                return $meta['meta_title'];
            }
        } catch (\Throwable $e) {
            // seo_meta may not exist yet during install
        }

        $site = (string) setting('seo.site_name', config('lindu.name', 'Lindu CMS'));
        $sep = (string) setting('seo.separator', config('lindu.seo.separator', ' | '));

        return $fallback !== '' ? $fallback.$sep.$site : $site;
    }

    public function render(?array $meta = null): string
    {
        $site = (string) setting('general.site_name', config('lindu.name', 'Lindu CMS'));
        $sep = (string) setting('seo.separator', ' | ');

        $m = array_merge([
            'title' => $site,
            'description' => setting('seo.meta_description', ''),
            'keywords' => setting('seo.meta_keywords', ''),
            'canonical' => url()->current(),
            'robots' => setting('seo.robots', 'index,follow'),
            'og_title' => null,
            'og_description' => null,
            'og_image' => null,
            'og_type' => 'website',
            'twitter_card' => setting('seo.twitter_card', 'summary_large_image'),
            'schema' => null,
        ], array_filter((array) $meta, fn ($v) => $v !== null && $v !== ''));

        $e = fn ($v) => htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        $title = $m['title'];
        if ($title === $site) {
            $h = "<title>{$e($title)}</title>\n";
        } else {
            $h = "<title>{$e($title)}</title>\n";
        }

        $h .= "<meta name=\"description\" content=\"{$e($m['description'])}\">\n";
        if (! empty($m['keywords'])) {
            $h .= "<meta name=\"keywords\" content=\"{$e($m['keywords'])}\">\n";
        }
        $h .= "<link rel=\"canonical\" href=\"{$e($m['canonical'])}\">\n";
        $h .= "<meta name=\"robots\" content=\"{$e($m['robots'])}\">\n";

        $ogImage = $m['og_image'] ?: setting('branding.og_image', '');
        $h .= "<meta property=\"og:site_name\" content=\"{$e($site)}\">\n";
        $h .= "<meta property=\"og:type\" content=\"{$e($m['og_type'])}\">\n";
        $h .= "<meta property=\"og:title\" content=\"{$e($m['og_title'] ?: $title)}\">\n";
        $h .= "<meta property=\"og:description\" content=\"{$e($m['og_description'] ?: $m['description'])}\">\n";
        $h .= "<meta property=\"og:url\" content=\"{$e($m['canonical'])}\">\n";
        if ($ogImage) {
            $h .= "<meta property=\"og:image\" content=\"{$e($ogImage)}\">\n";
        }

        $h .= "<meta name=\"twitter:card\" content=\"{$e($m['twitter_card'])}\">\n";
        $h .= "<meta name=\"twitter:title\" content=\"{$e($m['og_title'] ?: $title)}\">\n";
        $h .= "<meta name=\"twitter:description\" content=\"{$e($m['og_description'] ?: $m['description'])}\">\n";
        if ($ogImage) {
            $h .= "<meta name=\"twitter:image\" content=\"{$e($ogImage)}\">\n";
        }

        $schema = $m['schema'] ?: $this->organizationSchema();
        if ($schema) {
            $h .= "<script type=\"application/ld+json\">".$schema."</script>\n";
        }

        $custom = (string) setting('seo.custom_head', '');
        if ($custom !== '') {
            $h .= $custom."\n";
        }

        return $h;
    }

    public function organizationSchema(): string
    {
        $org = (array) setting('seo.schema_organization', []);
        $data = array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => $org['name'] ?? setting('general.site_name', config('lindu.name', 'Lindu CMS')),
            'url' => $org['url'] ?? url('/'),
            'logo' => $org['logo'] ?? setting('branding.logo', ''),
            'description' => $org['description'] ?? setting('seo.meta_description', ''),
            'email' => $org['email'] ?? setting('contact.email', ''),
            'telephone' => $org['telephone'] ?? setting('contact.phone', ''),
            'address' => $org['address'] ?? setting('contact.address', '') ?: null,
            'sameAs' => $org['sameAs'] ?? null,
        ], fn ($v) => $v !== null && $v !== '');

        if (count($data) <= 2) {
            return '';
        }

        $json = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return $json === false ? '' : $json;
    }

    public function breadcrumbSchema(array $crumbs): string
    {
        $items = [];
        foreach (array_values($crumbs) as $i => $c) {
            $items[] = array_filter([
                '@type' => 'ListItem',
                'position' => $i + 1,
                'name' => $c['name'] ?? '',
                'item' => $c['url'] ?? null,
            ]);
        }

        $json = json_encode([
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => $items,
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return $json === false ? '' : $json;
    }

    public function breadcrumb(array $crumbs): string
    {
        return '<nav class="breadcrumb" aria-label="Breadcrumb"><ol>';
        foreach ($crumbs as $i => $c) {
            $last = $i === array_key_last($crumbs);
            $name = htmlspecialchars((string) ($c['name'] ?? ''), ENT_QUOTES);
            $url = $c['url'] ?? null;
            $li = $last || ! $url
                ? "<li class=\"active\" aria-current=\"page\">{$name}</li>"
                : "<li><a href=\"".htmlspecialchars((string) $url, ENT_QUOTES)."\">{$name}</a></li>";
            $li = str_replace('<li', '<li itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem"', $li);
            echo $li;
        }

        return '</ol></nav>';
    }

    protected function add(string $loc, ?string $lastmod = null, string $freq = 'weekly', string $priority = '0.7'): void
    {
        $this->entries[] = array_filter([
            'loc' => $loc,
            'lastmod' => $lastmod,
            'changefreq' => $freq,
            'priority' => $priority,
        ]);
    }

    public function sitemap(): string
    {
        $this->entries = [];

        $this->add(url('/'), null, 'daily', '1.0');
        $this->add(url('/about'), null, 'monthly', '0.8');
        $this->add(url('/services'), null, 'monthly', '0.9');
        $this->add(url('/products'), null, 'monthly', '0.9');
        $this->add(url('/portfolio'), null, 'weekly', '0.8');
        $this->add(url('/team'), null, 'monthly', '0.6');
        $this->add(url('/testimonials'), null, 'monthly', '0.6');
        $this->add(url('/clients'), null, 'monthly', '0.6');
        $this->add(url('/faq'), null, 'monthly', '0.6');
        $this->add(url('/gallery'), null, 'weekly', '0.6');
        $this->add(url('/careers'), null, 'weekly', '0.7');
        $this->add(url('/contact'), null, 'monthly', '0.8');
        $this->add(url('/blog'), null, 'daily', '0.8');

        $safe = function (callable $fn) {
            try {
                $fn();
            } catch (\Throwable $e) {
                // Table may not exist before migrate; skip silently.
            }
        };

        $safe(fn () => Service::published()->ordered()->get()->each(
            fn ($s) => $this->add(url('/services/'.$s->slug), optional($s->updated_at)->toAtomString(), 'monthly', '0.8')
        ));

        $safe(fn () => Product::published()->ordered()->get()->each(
            fn ($p) => $this->add(url('/products/'.$p->slug), optional($p->updated_at)->toAtomString(), 'monthly', '0.8')
        ));

        $safe(fn () => Portfolio::published()->ordered()->get()->each(
            fn ($p) => $this->add(url('/portfolio/'.$p->slug), optional($p->updated_at)->toAtomString(), 'weekly', '0.7')
        ));

        $safe(fn () => GalleryAlbum::published()->get()->each(
            fn ($a) => $this->add(url('/gallery/'.$a->slug), optional($a->updated_at)->toAtomString(), 'weekly', '0.6')
        ));

        $safe(fn () => Career::published()->get()->each(
            fn ($c) => $this->add(url('/careers/'.$c->slug), optional($c->updated_at)->toAtomString(), 'weekly', '0.7')
        ));

        $safe(fn () => Faq::published()->get()->each(
            fn ($f) => $this->add(url('/faq#faq-'.$f->id), null, 'monthly', '0.5')
        ));

        $safe(fn () => Page::published()->get()->each(
            fn ($p) => $this->add(url('/p/'.$p->slug), optional($p->updated_at)->toAtomString(), 'weekly', '0.7')
        ));

        $safe(fn () => Post::where('status', 'published')->latest('published_at')->limit(2000)->get()->each(
            fn ($p) => $this->add(url('/blog/'.$p->slug), optional($p->updated_at)->toAtomString(), 'monthly', '0.7')
        ));

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";
        foreach ($this->entries as $e) {
            $xml .= "  <url>\n";
            $xml .= '    <loc>'.htmlspecialchars($e['loc'], ENT_QUOTES | ENT_XML1, 'UTF-8')."</loc>\n";
            if (! empty($e['lastmod'])) {
                $xml .= '    <lastmod>'.htmlspecialchars($e['lastmod'], ENT_QUOTES | ENT_XML1, 'UTF-8')."</lastmod>\n";
            }
            $xml .= '    <changefreq>'.$e['changefreq']."</changefreq>\n";
            $xml .= '    <priority>'.$e['priority']."</priority>\n";
            $xml .= "  </url>\n";
        }

        return $xml.'</urlset>';
    }

    public function robotsTxt(): string
    {
        $disallow = (string) setting('seo.robots_disallow', "/admin\n/install\n/login");

        $body = "User-agent: *\nAllow: /\n";
        foreach (array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $disallow))) as $line) {
            $body .= "Disallow: {$line}\n";
        }
        $body .= 'Sitemap: '.url('/sitemap.xml')."\n";

        return $body;
    }
}
