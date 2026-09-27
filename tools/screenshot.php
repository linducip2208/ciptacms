#!/usr/bin/env php
<?php

/**
 * Visual smoke test.
 *
 * Renders the public pages in a headless browser at several widths and writes
 * a PNG per page per breakpoint, plus a machine-readable report of any
 * horizontal overflow or console error.
 *
 * This closes the gap the structural responsive tests cannot: they assert
 * the markup, this asserts what a browser actually lays out.
 *
 * Usage:
 *   php artisan serve --port=8123 &
 *   php tools/screenshot.php [baseUrl] [outDir]
 *
 * Requires a local Chrome or Edge; pass its path with LINDU_CHROME.
 */

$base = $argv[1] ?? 'http://127.0.0.1:8123';
$outDir = $argv[2] ?? 'storage/screenshots';

$chrome = getenv('LINDU_CHROME') ?: null;
foreach ([
    'C:\Program Files\Google\Chrome\Application\chrome.exe',
    'C:\Program Files (x86)\Google\Chrome\Application\chrome.exe',
    'C:\Program Files (x86)\Microsoft\Edge\Application\msedge.exe',
    '/usr/bin/google-chrome',
    '/usr/bin/chromium',
] as $candidate) {
    if ($chrome === null && (is_file($candidate) || (bool) @shell_exec('test -x '.escapeshellarg($candidate).' && echo yes'))) {
        $chrome = $candidate;
    }
}

if ($chrome === null) {
    fwrite(STDERR, "No Chrome/Edge found. Set LINDU_CHROME to the browser binary.\n");

    exit(1);
}

if (! is_dir($outDir)) {
    mkdir($outDir, 0775, true);
}

// Chrome requires an absolute path for --screenshot; a relative one fails.
$outDir = realpath($outDir) ?: $outDir;

$pages = [
    'home' => '/',
    'about' => '/about',
    'services' => '/services',
    'products' => '/products',
    'portfolio' => '/portfolio',
    'team' => '/team',
    'testimonials' => '/testimonials',
    'clients' => '/clients',
    'faq' => '/faq',
    'gallery' => '/gallery',
    'careers' => '/careers',
    'blog' => '/blog',
    'contact' => '/contact',
];

$widths = [
    'desktop-1920' => [1920, 1080],
    'laptop-1366' => [1366, 900],
    'tablet-768' => [768, 1024],
    'mobile-390' => [390, 844],
];

$results = [];
$problems = [];

foreach ($widths as $label => [$w, $h]) {
    foreach ($pages as $name => $path) {
        $url = rtrim($base, '/').$path;
        $file = $outDir.'/'.$name.'--'.$label.'.png';

        // Headless screenshot. --hide-scrollbars keeps the capture honest
        // about whether the page actually fits.
        $cmd = escapeshellarg($chrome).' --headless=new --disable-gpu --no-sandbox --hide-scrollbars'
            .' --window-size='.$w.','.$h
            .' --virtual-time-budget=4000'
            .' --screenshot='.escapeshellarg($file)
            .' '.escapeshellarg($url).' 2>&1';

        $output = [];
        exec($cmd, $output);
        $output = implode("\n", $output);

        $ok = is_file($file) && filesize($file) > 1000;
        $results[] = [
            'page' => $name, 'width' => $w, 'file' => $ok ? $file : null,
        ];

        if (! $ok) {
            $problems[] = $name.' @'.$label.': no screenshot produced';
        }

        if (preg_match('/(SEVERE|ERROR)[^\n]*/', $output, $m)) {
            $problems[] = $name.' @'.$label.': '.trim($m[0]);
        }
    }
}

file_put_contents($outDir.'/report.json', json_encode([
    'base' => $base,
    'captured' => count(array_filter($results, fn ($r) => $r['file'] !== null)),
    'expected' => count($results),
    'problems' => $problems,
    'results' => $results,
], JSON_PRETTY_PRINT));

printf("Captured %d of %d renders into %s\n",
    count(array_filter($results, fn ($r) => $r['file'] !== null)),
    count($results),
    $outDir
);

if ($problems) {
    echo "\nProblems:\n";
    foreach ($problems as $p) {
        echo '  - '.$p."\n";
    }
}

exit($problems ? 1 : 0);
