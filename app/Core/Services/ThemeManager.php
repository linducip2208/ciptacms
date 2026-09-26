<?php

namespace App\Core\Services;

use App\Core\Themes\DesignTokens;
use App\Models\AppearanceOption;
use App\Models\Theme;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\View;
use RuntimeException;

/**
 * The theme engine.
 *
 * Activation is not a flag any more. A theme declares in its `theme.json`:
 *
 *   "tokens": { "colors": { "primary": "#0ea5e9" }, "layout": { ... } }
 *   "views":  true        // ship views/ to replace site.* views
 *
 * and activating it does two things that a visitor can see:
 *
 *   1. its tokens are emitted as CSS custom properties through
 *      ThemeManager::tokens(), which ThemeController::cssVariables() already
 *      renders in the site layout's <head>;
 *   2. if it ships views/ and opts in, its views/ directory is prepended to
 *      the view finder, so views/site/layout.blade.php replaces the core site
 *      layout without editing resources/views.
 *
 * Deactivating removes both. Tokens come from the manifest on disk rather than
 * a database copy, so there is no stale state to reset.
 */
class ThemeManager
{
    /** Filter plugins may hook to adjust the active theme's tokens. */
    public const TOKEN_FILTER = 'theme.tokens';

    public const ACTIVE_SETTING = 'theme.active';

    /** Group the customizer writes its operator overrides into. */
    public const CUSTOMIZER_GROUP = 'customizer';

    protected ?string $activeSlug = null;

    protected bool $activeSlugResolved = false;

    protected bool $viewsApplied = false;

    /* ------------------------------------------------------------------ */
    /* Discovery                                                           */
    /* ------------------------------------------------------------------ */

    /**
     * Every theme directory holding a readable theme.json.
     *
     * @return array<int, array<string, mixed>>
     */
    public function discover(): array
    {
        $base = config('lindu.themes_path');
        $found = [];
        if (! is_dir($base)) {
            return $found;
        }
        foreach (File::directories($base) as $dir) {
            $meta = $dir.'/theme.json';
            if (! File::exists($meta)) {
                continue;
            }
            $j = json_decode(File::get($meta), true);
            if (! is_array($j) || empty($j['slug']) || ! is_string($j['slug'])) {
                continue;
            }
            if (! preg_match('/^[A-Za-z0-9][A-Za-z0-9._-]{0,63}$/', $j['slug']) || str_contains($j['slug'], '..')) {
                continue;
            }
            $j['path'] = $dir;
            $j['dir'] = basename($dir);
            $found[] = $j;
        }

        return $found;
    }

    /** The manifest for $slug, or null when no such theme directory exists. */
    public function find(string $slug): ?array
    {
        if (! is_string($slug) || ! preg_match('/^[A-Za-z0-9][A-Za-z0-9._-]{0,63}$/', $slug) || str_contains($slug, '..')) {
            return null;
        }
        foreach ($this->discover() as $theme) {
            if ($theme['slug'] === $slug) {
                return $theme;
            }
        }

        return null;
    }

    public function syncRegistry(): void
    {
        foreach ($this->discover() as $t) {
            Theme::updateOrCreate(['slug' => $t['slug']], [
                'name' => $t['name'] ?? $t['slug'],
                'version' => $t['version'] ?? '1.0.0',
                'author' => $t['author'] ?? null,
                'description' => $t['description'] ?? null,
                'meta' => $t,
            ]);
        }
    }

    /* ------------------------------------------------------------------ */
    /* Activation                                                          */
    /* ------------------------------------------------------------------ */

    /**
     * Activate a theme. Unknown slugs are refused rather than silently
     * deactivating whatever was active before.
     *
     * @throws RuntimeException when the slug is not a discovered theme
     */
    public function activate(string $slug): void
    {
        $manifest = $this->find($slug);
        if ($manifest === null) {
            throw new RuntimeException("Unknown theme [{$slug}].");
        }

        $row = Theme::where('slug', $slug)->first();
        if (! $row) {
            $row = Theme::updateOrCreate(['slug' => $slug], [
                'name' => $manifest['name'] ?? $slug,
                'version' => $manifest['version'] ?? '1.0.0',
                'author' => $manifest['author'] ?? null,
                'description' => $manifest['description'] ?? null,
                'meta' => $manifest,
            ]);
        } else {
            // Keep the registry row in step with the manifest on disk so the
            // admin screen shows the version that is actually running.
            $row->update([
                'name' => $manifest['name'] ?? $row->name,
                'version' => $manifest['version'] ?? $row->version,
                'meta' => $manifest,
            ]);
        }

        Theme::query()->update(['is_active' => false]);
        $row->update(['is_active' => true]);

        app(SettingService::class)->set(self::ACTIVE_SETTING, $slug, 'text', 'branding');
        $this->reset();

        try {
            app(AuditService::class)->log('activate_theme', $row->fresh());
        } catch (\Throwable $e) {
            report($e);
        }
    }

    /**
     * Turn a theme off. Its tokens and its view overrides stop applying, so
     * the public site falls back to the core layout and core colours.
     *
     * @throws RuntimeException when no theme with that slug is registered
     */
    public function deactivate(string $slug): void
    {
        $row = Theme::where('slug', $slug)->first();
        if (! $row) {
            throw new RuntimeException("Unknown theme [{$slug}].");
        }

        $row->update(['is_active' => false]);
        app(SettingService::class)->set(self::ACTIVE_SETTING, '', 'text', 'branding');
        $this->reset();

        try {
            app(AuditService::class)->log('deactivate_theme', $row->fresh());
        } catch (\Throwable $e) {
            report($e);
        }
    }

    public function active(): ?Theme
    {
        try {
            return Theme::where('is_active', true)->first();
        } catch (\Throwable $e) {
            return null;
        }
    }

    /** The active theme's slug, or null. Cached for the request. */
    public function activeSlug(): ?string
    {
        if ($this->activeSlugResolved) {
            return $this->activeSlug;
        }
        $this->activeSlugResolved = true;

        try {
            $this->activeSlug = $this->active()?->slug;
        } catch (\Throwable $e) {
            $this->activeSlug = null;
        }

        if (! $this->activeSlug) {
            $configured = (string) setting(self::ACTIVE_SETTING, '');
            $this->activeSlug = $this->find($configured) ? $configured : null;
        }

        return $this->activeSlug;
    }

    /** The active theme's manifest, or null. */
    public function manifest(): ?array
    {
        $slug = $this->activeSlug();

        return $slug ? $this->find($slug) : null;
    }

    public function isActive(string $slug): bool
    {
        return $this->activeSlug() === $slug;
    }

    /* ------------------------------------------------------------------ */
    /* Design tokens                                                       */
    /* ------------------------------------------------------------------ */

    /**
     * The design tokens a visitor gets right now, lowest precedence first:
     *
     *   1. the active theme's `settings` block (legacy flat keys),
     *   2. the active theme's `tokens` block,
     *   3. operator overrides saved by the theme customizer,
     *   4. whatever active plugins return from the `theme.tokens` filter.
     *
     * @return array<string, string>
     */
    public function tokens(): array
    {
        $manifest = $this->manifest();

        $base = $manifest ? DesignTokens::normalise([
            'settings' => (array) ($manifest['settings'] ?? []),
            'tokens' => (array) ($manifest['tokens'] ?? []),
        ]) : [];

        $tokens = DesignTokens::merge($base, $this->customizerTokens());

        try {
            $filtered = app(PluginManager::class)->applyFilter(self::TOKEN_FILTER, $tokens, [
                'theme' => $manifest['slug'] ?? null,
            ]);
            if (is_array($filtered)) {
                $tokens = $filtered;
            }
        } catch (\Throwable $e) {
            report($e);
        }

        return $tokens;
    }

    /** The tokens rendered into the site layout's <head>. */
    public function tokenCss(): string
    {
        try {
            return DesignTokens::toCss($this->tokens());
        } catch (\Throwable $e) {
            report($e);

            return '';
        }
    }

    /** Operator overrides from the customizer, in token-key form. */
    public function customizerTokens(): array
    {
        try {
            $rows = AppearanceOption::where('group', self::CUSTOMIZER_GROUP)->pluck('value', 'key')->all();
        } catch (\Throwable $e) {
            return [];
        }

        $out = [];
        foreach ($rows as $key => $value) {
            if ($value === null || $value === '') {
                continue;
            }
            $key = strtolower(trim((string) $key));
            if ($key === '' || str_contains($key, ';') || str_contains($key, '{')) {
                continue;
            }
            // `sticky_header` is a boolean the layout does not read; keep it out
            // of the stylesheet rather than emitting a junk variable.
            if (str_ends_with($key, '_header') || ! preg_match('/^[a-z0-9._-]+$/', $key)) {
                continue;
            }
            $out[$key] = (string) $value;
        }

        return $out;
    }

    /* ------------------------------------------------------------------ */
    /* Layout resolution                                                   */
    /* ------------------------------------------------------------------ */

    /**
     * Does the active theme replace the shipped site views?
     *
     * A theme opts in with `"views": true` and a `views/` directory holding any
     * `site.*` view — including `site/layout.blade.php`, which is how a theme
     * changes the page chrome.
     */
    public function hasViewOverrides(): bool
    {
        $manifest = $this->manifest();
        if (! $manifest) {
            return false;
        }
        if (empty($manifest['views']) && empty($manifest['view_overrides'])) {
            return false;
        }

        return is_dir($manifest['path'].'/views');
    }

    public function viewPath(): ?string
    {
        return $this->hasViewOverrides() ? $this->manifest()['path'].'/views' : null;
    }

    /**
     * Prepend the active theme's views/ directory to the view finder so its
     * site views win over the shipped ones. Called once per request by
     * SiteController before it renders, and guarded so a theme folder that
     * disappears mid-request cannot break the site.
     */
    public function applyViewOverrides(): bool
    {
        if ($this->viewsApplied) {
            return false;
        }
        $this->viewsApplied = true;

        try {
            $path = $this->viewPath();
            if ($path === null) {
                return false;
            }
            View::getFinder()->prependLocation($path);

            return true;
        } catch (\Throwable $e) {
            report($e);

            return false;
        }
    }

    /** Forget the per-request active-theme cache. */
    public function reset(): void
    {
        $this->activeSlug = null;
        $this->activeSlugResolved = false;
    }
}
