<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Illuminate\View\Compilers\BladeCompiler;
use Tests\TestCase;

/**
 * Static checks that catch whole classes of runtime-only bugs:
 *
 *  1. A Blade template that does not compile. The nastiest case is an
 *     `@endif` sitting inside an HTML attribute value — Blade's directive
 *     regex requires a non-word boundary before the "@", so inside
 *     style="…:6@endif" the @endif is never matched and the @if is left
 *     dangling. That only explodes when a visitor hits the page.
 *
 *  2. A route() name referenced in a template that does not exist. Also a
 *     500-at-runtime-only failure.
 */
class TemplateIntegrityTest extends TestCase
{
    /**
     * Data providers run before the application boots, so base_path() and
     * the facade root are unavailable here — resolve the paths by hand.
     */
    public static function bladeFiles(): array
    {
        $base = dirname(__DIR__, 2);
        $files = [];

        foreach (['resources/views', 'modules', 'themes', 'plugins'] as $root) {
            $path = $base.'/'.$root;
            if (! is_dir($path)) {
                continue;
            }
            $it = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS)
            );
            foreach ($it as $f) {
                if ($f->isFile() && str_ends_with($f->getFilename(), '.blade.php')) {
                    $files[] = $f->getPathname();
                }
            }
        }

        sort($files);

        $cases = [];
        foreach ($files as $i => $f) {
            $cases['tpl_'.$i] = [$f];
        }

        return $cases;
    }

    protected function compiler(): BladeCompiler
    {
        return new BladeCompiler($this->app->make('files'), $this->app->storagePath('framework/views'));
    }

    /**
     * PHPUnit 12 ignores the @dataProvider annotation, so the attribute is
     * required for the provider to run at all.
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('bladeFiles')]
    public function test_blade_template_compiles(string $file): void
    {
        $compiled = $this->compiler()->compileString(File::get($file));

        // token_get_all(..., TOKEN_PARSE) is a full syntax check and runs
        // in-process. Shelling out to `php -l` crashes with a stack
        // overflow on deeply nested templates, which produced a false
        // failure here before.
        try {
            token_get_all($compiled, TOKEN_PARSE);
        } catch (\ParseError $e) {
            $this->fail('Compiled template for '.$file.' is not valid PHP: '.$e->getMessage());
        }

        $this->assertNotSame('', trim($compiled), 'Template produced empty output: '.$file);
    }

    public function test_every_route_name_used_in_templates_exists(): void
    {
        $known = [];
        foreach (Route::getRoutes() as $route) {
            if ($route->getName()) {
                $known[$route->getName()] = true;
            }
        }

        $problems = [];

        foreach (self::bladeFiles() as $case) {
            $file = $case[0];
            if (preg_match_all("/route\(\s*'([a-zA-Z0-9_.\\-]+)'/", File::get($file), $m)) {
                foreach ($m[1] as $name) {
                    if (! isset($known[$name])) {
                        $problems[] = $name.' in '.str_replace(base_path().'\\', '', $file);
                    }
                }
            }
        }

        $this->assertSame([], $problems, "Unknown route names in templates:\n".implode("\n", $problems));
    }

    /**
     * Blade only matches a directive when the character before the "@" is a
     * non-word character. So `...:6@endif"` silently leaves the @if open,
     * while `... @endif"` compiles fine. Assert the broken form never appears.
     */
    public function test_no_blade_directive_follows_a_word_character(): void
    {
        $bad = [];

        foreach (self::bladeFiles() as $case) {
            $file = $case[0];
            foreach (preg_split('/\r\n|\r|\n/', File::get($file)) as $n => $line) {
                if (preg_match('/\w@(if|else|elseif|endif|foreach|endforeach|forelse|empty|endforelse|php|endphp)\b/', $line)) {
                    $bad[] = basename($file).':'.($n + 1).' — '.trim($line);
                }
            }
        }

        $this->assertSame(
            [],
            $bad,
            "Blade directive directly after a word character (Blade will NOT match it, leaving the block open):\n".implode("\n", $bad)
        );
    }
}
