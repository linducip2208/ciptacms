<?php

/*
 * Blade syntax linter.
 *
 * Compiles every .blade.php file and validates the result. This catches
 * directive mistakes that only surface at runtime, such as an @endif that
 * Blade fails to match because it sits inside an HTML attribute value.
 *
 * Validation uses token_get_all(..., TOKEN_PARSE) rather than shelling out
 * to `php -l`: the sub-process crashes with a stack overflow on deeply
 * nested templates, and token_get_all parses in-process.
 *
 * Usage: php tools/blade-lint.php [path …]
 */

require __DIR__.'/../vendor/autoload.php';

$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$compiler = new Illuminate\View\Compilers\BladeCompiler(
    app('files'),
    storage_path('framework/views')
);

$roots = array_slice($argv, 1);
if (! $roots) {
    $roots = ['resources/views', 'modules', 'themes', 'plugins'];
}

$files = [];
foreach ($roots as $root) {
    if (is_file($root)) {
        $files[] = $root;

        continue;
    }
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
$failed = [];

foreach ($files as $file) {
    try {
        $compiled = $compiler->compileString(file_get_contents($file));
    } catch (\Throwable $e) {
        $failed[] = [$file, 'compile: '.$e->getMessage()];

        continue;
    }

    try {
        token_get_all($compiled, TOKEN_PARSE);
    } catch (\ParseError $e) {
        $failed[] = [$file, 'invalid PHP after compile: '.$e->getMessage()];

        continue;
    }
}

printf("Checked %d blade files.\n", count($files));

if ($failed) {
    echo "\nFAILURES:\n";
    foreach ($failed as [$file, $msg]) {
        echo "  {$file}\n    {$msg}\n";
    }
    exit(1);
}

echo "All blade templates compile to valid PHP.\n";
exit(0);
