<?php

/*
 * Report every route() name used in a Blade template that is not registered.
 *
 * A missing route name is a 500 at runtime, and only on the page that uses
 * it, so it is easy to ship. Run this after adding or renaming routes.
 *
 * Usage: php tools/route-lint.php
 */

require __DIR__.'/../vendor/autoload.php';

$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$known = [];
foreach ($app->make('router')->getRoutes() as $route) {
    if ($route->getName()) {
        $known[$route->getName()] = true;
    }
}

$files = [];
foreach (['resources/views', 'modules', 'themes', 'plugins'] as $root) {
    if (! is_dir($root)) {
        continue;
    }
    $it = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)
    );
    foreach ($it as $f) {
        if ($f->isFile() && str_ends_with($f->getFilename(), '.blade.php')) {
            $files[] = $f->getPathname();
        }
    }
}
sort($files);

// Controllers, models and seeders reference route() too — a wrong name
// there is the same 500-at-runtime bug.
$codeFiles = [];
foreach (['app', 'routes', 'database', 'modules'] as $root) {
    if (! is_dir($root)) {
        continue;
    }
    $it = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)
    );
    foreach ($it as $f) {
        if ($f->isFile() && str_ends_with($f->getFilename(), '.php')) {
            $codeFiles[] = $f->getPathname();
        }
    }
}
sort($codeFiles);

$missing = [];

foreach ($files as $file) {
    $src = file_get_contents($file);
    if (preg_match_all("/route\(\s*'([a-zA-Z0-9_.\-]+)'/", $src, $m)) {
        foreach ($m[1] as $name) {
            if (! isset($known[$name])) {
                $missing[$name][] = $file;
            }
        }
    }
}

// Only flag named routes in code, not dynamic route() calls.
// Exclude $request->route('x') which reads a route *parameter*.
foreach ($codeFiles as $file) {
    if (in_array($file, $files, true)) {
        continue;
    }
    $src = file_get_contents($file);
    if (preg_match_all('/(?<![>\w])route\(\s*\'([a-zA-Z0-9_.\-]+)\'\s*\)/', $src, $m, PREG_OFFSET_CAPTURE)) {
        foreach ($m[1] as [$name, $offset]) {
            if (str_starts_with($name, 'api.') || str_ends_with($name, '.*')) {
                continue;
            }
            // Skip "$request->route('id')" and friends.
            $before = substr($src, max(0, $offset - 24), 24);
            if (str_contains($before, '->') || str_ends_with(rtrim($before), '$r') || str_ends_with(rtrim($before), 'request')) {
                continue;
            }
            if (! isset($known[$name])) {
                $missing[$name][] = $file;
            }
        }
    }
}

if ($missing) {
    ksort($missing);
    echo "Unknown route names referenced in templates:\n\n";
    foreach ($missing as $name => $where) {
        echo "  {$name}\n";
        foreach (array_unique($where) as $f) {
            echo "      {$f}\n";
        }
    }
    echo "\n";
    exit(1);
}

printf("Checked %d blade files — all route() names exist.\n", count($files));
exit(0);
