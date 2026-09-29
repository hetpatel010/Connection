<?php

$path = $argv[1] ?? '.env';

$overrides = [
    'APP_ENV' => 'local',
    'APP_DEBUG' => 'true',
    'APP_RUNTIME' => 'web',
    'APP_URL' => 'https://billing_saas.test',
    'SESSION_DRIVER' => 'database',
    'CACHE_STORE' => 'database',
    'QUEUE_CONNECTION' => 'database',
    'LOG_CHANNEL' => 'stack',
    'LOG_LEVEL' => 'debug',
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
