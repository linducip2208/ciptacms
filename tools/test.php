#!/usr/bin/env php
<?php

/**
 * Compact test runner: prints one line per test with the actual reason,
 * without the full stack trace PHPUnit dumps.
 *
 * Usage: php tools/test.php [filter]
 */

$filter = $argv[1] ?? null;

$cmd = 'php artisan test --without-tty';
if ($filter) {
    $cmd .= ' --filter='.escapeshellarg($filter);
}

$output = [];
$exit = 0;
exec($cmd.' 2>&1', $output, $exit);

$text = implode("\n", $output);

if (preg_match('/"tests":(\d+),"passed":(\d+),"assertions":(\d+)/', $text, $m)) {
    printf("tests=%s passed=%s assertions=%s\n", $m[1], $m[2], $m[3]);
}

if (preg_match('/"errors":(\d+)/', $text, $m) && $m[1] !== '0') {
    printf("errors=%s\n", $m[1]);
}

// Print the distinct failure reasons.
if (preg_match_all('/"message":"(.*?)","trace"/s', $text, $mm)) {
    $seen = [];
    foreach ($mm[1] as $raw) {
        $msg = json_decode('"'.$raw.'"', true);
        $msg = preg_split('/\R/', $msg)[0];
        $msg = trim(preg_replace('/\s+/', ' ', $msg));
        if ($msg === '' || isset($seen[$msg])) {
            continue;
        }
        $seen[$msg] = true;
        echo "  - ".substr($msg, 0, 220)."\n";
    }
}

// The root exception is more useful than the assertion message.
if (preg_match('/The following exception occurred during the last request:(.*?)(?:Next |\r?\n--|\z)/s', $text, $ex)) {
    $body = trim(preg_replace('/\\\\/', '\\', $ex[1]));
    $lines = array_values(array_filter(array_map('trim', preg_split('/\R/', $body))));
    $interesting = array_values(array_filter($lines, fn ($l) => $l !== '' && ! str_starts_with($l, '#')));
    if ($interesting) {
        echo "  reason: ".substr($interesting[0], 0, 300)."\n";
    }
}

if (preg_match_all('/"file":"([^"]+)","line":(\d+)/', $text, $mm)) {
    foreach (array_unique(array_map(fn ($i) => $mm[1][$i].':'.$mm[2][$i], array_keys($mm[1]))) as $loc) {
        echo "  at ".$loc."\n";
    }
}

exit($exit === 0 ? 0 : 1);
