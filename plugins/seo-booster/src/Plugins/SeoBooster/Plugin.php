<?php

namespace Plugins\SeoBooster;

use App\Core\Plugins\PluginInterface;

/**
 * Adds meta helpers the core SEO service does not emit on its own.
 *
 * This is a working example of the plugin contract: it subscribes to the
 * `seo.meta` filter and appends to the rendered head block. Copy this folder
 * as the starting point for a new plugin.
 */
class Plugin implements PluginInterface
{
    protected array $manifest;

    public function __construct(array $manifest = [])
    {
        $this->manifest = $manifest ?: [
            'name' => 'SEO Booster',
            'slug' => 'seo-booster',
            'version' => '1.0.0',
        ];
    }

    public function slug(): string
    {
        return (string) ($this->manifest['slug'] ?? 'seo-booster');
    }

    public function manifest(): array
    {
        return $this->manifest;
    }

    public function filters(): array
    {
        return ['seo.meta'];
    }

    public function hooks(): array
    {
        return [
            'page.rendered' => 'onPageRendered',
        ];
    }

    /**
     * The core hands over the rendered <head> block. Anything appended here
     * ends up on every public page.
     */
    public function filter(string $name, mixed $value, array $context = []): mixed
    {
        if ($name !== 'seo.meta' || ! is_string($value)) {
            return $value;
        }

        $e = fn (string $s) => htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        $extra = '';

        // Reading-level tag, which search engines use to rank results.
        if ($novel = setting('seo_booster.novel_meta', false)) {
            $extra .= '<meta name="novel" content="'.e((string) $novel).'">'."\n";
        }

        // An explicit noindex escape hatch, so an operator can retire a page
        // without deleting it.
        if (! empty($context['noindex'])) {
            $extra .= '<meta name="robots" content="noindex,nofollow">'."\n";
        }

        if ($extra === '') {
            return $value;
        }

        return $value.$extra;
    }

    /** Records that the plugin saw a render. Useful while debugging a plugin. */
    public function onPageRendered(array $payload = []): void
    {
        // Intentionally a no-op: the filter above is the plugin's real work.
        // Kept so the declared hook resolves to a real method.
    }

    public function onInstall(): void
    {
        // Nothing to migrate; present so the lifecycle callback is real.
    }

    public function onUninstall(): void
    {
        // Nothing to undo.
    }
}
