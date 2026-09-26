<?php

namespace App\Core\Plugins;

/**
 * The contract every folder-based plugin implements.
 *
 * A plugin is a directory under `plugins/` holding a `plugin.json` manifest
 * and a class whose FQCN the manifest names in its `class` key. The class file
 * is expected at `<plugin dir>/src/<Class path>.php`.
 *
 * Contract version 1:
 *   - manifest() returns the decoded plugin.json, with `path` and `dir` added.
 *   - filters() lists the filter names the plugin wants to receive. The core
 *     calls filter() only for those names, in plugin priority order, and uses
 *     the return value as the new value.
 *   - hooks() maps an event name to a public method on this class. The core
 *     registers that method as a Laravel event listener, so `event('x')`
 *     reaches plugin code without the core knowing the plugin exists.
 *   - onInstall()/onUninstall() are optional lifecycle callbacks.
 */
interface PluginInterface
{
    /** Bumped only for breaking changes to this interface. */
    public const CONTRACT_VERSION = 1;

    /** The plugin's slug; must equal the `slug` in plugin.json. */
    public function slug(): string;

    /** The decoded plugin.json plus the resolved `path` and `dir` keys. */
    public function manifest(): array;

    /**
     * Filters this plugin participates in.
     *
     * @return array<int, string>
     */
    public function filters(): array;

    /**
     * Events this plugin listens to, as `event name => public method name`.
     * A list of event names is also accepted: the method is then derived as
     * `on` . studly(event name), e.g. `page.rendered` -> `onPageRendered`.
     *
     * @return array<string, string|int>
     */
    public function hooks(): array;

    /**
     * Transform a filtered value. Return the new value; returning null keeps
     * the previous value (so a plugin can decline by returning null).
     */
    public function filter(string $name, mixed $value, array $context = []): mixed;

    /** Called after a successful install(). */
    public function onInstall(): void;

    /** Called after a successful uninstall(). */
    public function onUninstall(): void;
}
