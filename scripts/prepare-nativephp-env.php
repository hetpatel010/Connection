<?php

$path = $argv[1] ?? '.env';

$overrides = [
    'APP_ENV' => 'production',
    'APP_DEBUG' => 'false',
    'APP_RUNTIME' => 'desktop',
    'APP_URL' => 'http://127.0.0.1',
    'SESSION_DRIVER' => 'file',
    'CACHE_STORE' => 'file',
    'QUEUE_CONNECTION' => 'sync',
    'LOG_CHANNEL' => 'stack',
    'LOG_LEVEL' => 'warning',
];

$lines = file_exists($path) ? file($path, FILE_IGNORE_NEW_LINES) : [];
$seen = array_fill_keys(array_keys($overrides), false);
$updated = [];

foreach ($lines as $line) {
    if (! str_contains($line, '=') || str_starts_with(trim($line), '#')) {
        $updated[] = $line;

        continue;
    }

    [$key] = explode('=', $line, 2);

    if (array_key_exists($key, $overrides)) {
        $updated[] = $key.'='.$overrides[$key];
        $seen[$key] = true;

        continue;
    }

    $updated[] = $line;
}

foreach ($overrides as $key => $value) {
    if (! $seen[$key]) {
        $updated[] = $key.'='.$value;
    }
}

file_put_contents($path, implode(PHP_EOL, $updated).PHP_EOL);
