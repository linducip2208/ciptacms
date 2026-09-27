<?php

/*
|--------------------------------------------------------------------------
| Release packaging
|--------------------------------------------------------------------------
| Builds a distributable archive of the product.
|
| A release must not carry development state. This script builds the file
| list from an explicit allow-list of what ships, then subtracts anything that
| is still present by accident (.env, caches, logs, build output, VCS data).
| It also refuses to build if a secret is present, rather than relying on
| .gitignore alone.
|
| Usage:
|   php tools/release.php [version]
|   php tools/release.php 1.0.0 --out=dist
|
| Exits non-zero if the archive is unsafe to ship, so a release cannot be
| produced by accident.
*/

$root = dirname(__DIR__);
$version = $argv[1] ?? trim((string) @file_get_contents($root.'/.release-version') ?: '');
$outDir = 'dist';

foreach (array_slice($argv, 2) as $arg) {
    if (str_starts_with($arg, '--out=')) {
        $outDir = substr($arg, 6);
    }
}

if (! preg_match('/^\d+\.\d+\.\d+$/', $version)) {
    fwrite(STDERR, "Version is required as the first argument, e.g. 1.0.0\n");

    exit(1);
}

$distPath = $root.'/'.$outDir;
$zipName = 'CiptaCMS-v'.$version.'.zip';
$zipPath = $distPath.'/'.$zipName;

/*
| What the product is made of. Anything not listed here does not ship, so a
| stray directory cannot end up in a customer's hands by accident.
*/
$include = [
    'app', 'bootstrap', 'config', 'database', 'lang', 'modules', 'plugins',
    'public', 'resources', 'routes', 'storage', 'themes',
];

$includeFiles = [
    'artisan', 'composer.json', 'composer.lock', 'package.json', 'package-lock.json',
    'vite.config.js', 'phpunit.xml', '.editorconfig', '.gitattributes', '.gitignore',
    '.env.example', '.env.production.example', 'Dockerfile', 'docker-compose.yml',
    'LICENSE.md', 'README.md', 'README.en.md', 'README.id.md', 'README.ar.md',
    'CHANGELOG.md', 'INSTALL.md', 'UPGRADE.md', 'DEPLOYMENT.md', 'SECURITY.md',
    'TROUBLESHOOTING.md', 'ARCHITECTURE.md', 'DATABASE.md', 'DEVELOPMENT.md',
    'API.md', 'ARCHITECTURE.md', 'MODULES.md', 'PLUGINS.md', 'THEMES.md',
    'PAGE_BUILDER.md', 'FORM_BUILDER.md', 'DATA_BUILDER.md', 'MENU_ENGINE.md',
    'WORKFLOW.md', 'WHITE_LABEL.md', 'COMPANY_PROFILE.md', 'UPDATES.md',
    'SAAS.md', 'USER_GUIDE.md', 'ADMIN_GUIDE.md', 'DEVELOPER.md',
];

/*
| Never ship these, whatever the directory listing says.
*/
$excludePaths = [
    '.git', '.github', '.kilo', '.idea', '.vscode', 'node_modules', 'vendor',
    'tests', 'tools', 'deploy', 'dist',
    '.env', '.env.backup', '.env.local', '.env.testing',
    'storage/logs', 'storage/framework/cache', 'storage/framework/sessions',
    'storage/framework/views', 'storage/framework/testing',
    'storage/app/lindu', 'storage/app/.license.lock', 'storage/app/private',
    'public/build', 'public/hot', 'public/storage',
    'storage/screenshots', 'storage/admin-empty-state.txt',
];

$excludeExtensions = ['log', 'tmp', 'bak', 'orig', 'rej', 'sqlite', 'sqlite-journal', 'db'];

$isExcluded = function (string $relative) use ($excludePaths): bool {
    $relative = str_replace('\\', '/', $relative);
    foreach ($excludePaths as $bad) {
        if ($relative === $bad || str_starts_with($relative, $bad.'/')) {
            return true;
        }
    }

    return false;
};

// ---------------------------------------------------------------------------
// Safety checks — a release must never carry a secret.
// ---------------------------------------------------------------------------

/*
| Safety checks.
|
| These run against the files that are about to be archived, not the working
| tree: a developer's local .env never enters the archive, so its presence is
| not a release problem. What matters is that nothing on the include list
| carries a live credential.
|
| An activated licence lock is a different case and does abort: shipping one
| would grant every customer the same licence.
*/
$problems = [];

$licenseLock = $root.'/storage/app/lindu/license.json';
if (is_file($licenseLock)) {
    $problems[] = 'an activated licence lock would ship with the release';
}

$secretPatterns = [
    '/APP_KEY\s*=\s*base64:[A-Za-z0-9+\/=]{20,}/',
    '/\b(STRICTLY_BYPASS|LICENSE_KEY|PROD_API_KEY)\s*=\s*\S{12,}/',
    '/-----BEGIN (RSA |EC |OPENSSH )?PRIVATE KEY-----/',
];

/** Extensions that legitimately contain key-looking text. */
$skippable = ['md', 'example', 'lock', 'json'];

foreach ($include as $dir) {
    $path = $root.'/'.$dir;
    if (! is_dir($path)) {
        continue;
    }
    $it = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS)
    );
    foreach ($it as $f) {
        /** @var SplFileInfo $f */
        if (! $f->isFile()) {
            continue;
        }
        $relative = ltrim(str_replace('\\', '/', substr($f->getPathname(), strlen($root) + 1)), '/');

        if ($isExcluded($relative) || in_array(strtolower($f->getExtension()), $skippable, true)) {
            continue;
        }
        // Only text-ish files; a binary scan produces false positives.
        if (filesize($f->getPathname()) > 2 * 1024 * 1024) {
            continue;
        }
        $contents = (string) @file_get_contents($f->getPathname());
        if (str_contains($contents, "\0")) {
            continue;
        }
        foreach ($secretPatterns as $pattern) {
            if (preg_match($pattern, $contents)) {
                $problems[] = "possible live secret in {$relative}";
                break;
            }
        }
    }
}

// ---------------------------------------------------------------------------

if ($problems) {
    fwrite(STDERR, "REFUSING TO BUILD — the release would ship unsafe state:\n");
    foreach ($problems as $p) {
        fwrite(STDERR, '  - '.$p."\n");
    }

    exit(1);
}

// ---------------------------------------------------------------------------
// Collect files
// ---------------------------------------------------------------------------

$files = [];

foreach ($include as $dir) {
    $path = $root.'/'.$dir;
    if (! is_dir($path)) {
        continue;
    }
    $it = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS)
    );
    foreach ($it as $f) {
        /** @var SplFileInfo $f */
        if (! $f->isFile()) {
            continue;
        }
        $relative = ltrim(str_replace('\\', '/', substr($f->getPathname(), strlen($root) + 1)), '/');

        if ($isExcluded($relative)) {
            continue;
        }
        if (in_array(strtolower($f->getExtension()), $excludeExtensions, true)) {
            continue;
        }
        $files[] = $relative;
    }
}

foreach ($includeFiles as $file) {
    if (is_file($root.'/'.$file) && ! $isExcluded($file)) {
        $files[] = $file;
    }
}

$files = array_values(array_unique($files));
sort($files);

// ---------------------------------------------------------------------------
// Build
// ---------------------------------------------------------------------------

if (! is_dir($distPath) && ! mkdir($distPath, 0775, true)) {
    fwrite(STDERR, "Could not create {$outDir}/\n");

    exit(1);
}

$manifestPath = $distPath.'/CiptaCMS-v'.$version.'.manifest.json';
$manifest = [
    'product' => 'CiptaCMS',
    'version' => $version,
    'built_at' => gmdate('c'),
    'php' => PHP_VERSION,
    'file_count' => count($files),
    'files' => $files,
];
file_put_contents($manifestPath, json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

if (file_exists($zipPath)) {
    unlink($zipPath);
}

$zip = new ZipArchive();
if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
    fwrite(STDERR, "Could not open the archive\n");

    exit(1);
}

$prefix = 'CiptaCMS-v'.$version.'/';

foreach ($files as $relative) {
    $zip->addFile($root.'/'.$relative, $prefix.$relative);
}

$zip->addFile($manifestPath, $prefix.'MANIFEST.json');
$zip->close();

$size = filesize($zipPath);

printf("Built %s\n", $zipName);
printf("  files:   %d\n", count($files));
printf("  size:    %s\n", number_format($size / 1048576, 2).' MB');
printf("  manifest: %s\n", basename($manifestPath));

// Re-verify what actually went in, rather than trusting the add list.
$check = new ZipArchive();
$check->open($zipPath);
$leaked = [];
$allowedEnvTemplates = ['.env.example', '.env.production.example'];

for ($i = 0; $i < $check->numFiles; $i++) {
    $name = str_replace('\\', '/', $check->getNameIndex($i));
    $leaf = basename($name);

    if (in_array($leaf, $allowedEnvTemplates, true)) {
        // The install templates are supposed to ship; the real .env is not.
        if (str_contains($name, '/.env.')) {
            continue;
        }
    }

    foreach (['/.git/', '/node_modules/', '/vendor/', '/.env', 'storage/logs/', '/tests/', '/public/build/', '/tools/', '/.kilo/'] as $needle) {
        if (str_contains($name, $needle)) {
            $leaked[] = $name;
            break;
        }
    }
}
$check->close();

if ($leaked) {
    fwrite(STDERR, "\nWARNING — the archive contains entries it should not:\n");
    foreach (array_unique($leaked) as $l) {
        fwrite(STDERR, '  - '.$l."\n");
    }

    exit(1);
}

echo "  verified: no .git, node_modules, vendor, real .env, logs, tests or build output\n";
exit(0);
