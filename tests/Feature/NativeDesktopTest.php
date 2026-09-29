<?php

use App\Providers\NativeAppServiceProvider;
use Illuminate\Http\Request;

test('nativephp configuration uses sveo billing defaults', function () {
    expect(config('nativephp.app_id'))->toBe('in.sveo.billing')
        ->and(config('nativephp.author'))->toBe('Sveo Technologies')
        ->and(config('nativephp.updater.enabled'))->toBeFalse()
        ->and(config('nativephp.prebuild'))->toContain('npm run build');
});

test('platform runtime resolves to desktop when nativephp is running', function () {
    config([
        'platform.runtime' => 'web',
        'platform.database_connection' => 'pgsql',
    ]);

    putenv('NATIVEPHP_RUNNING=true');
    $_ENV['NATIVEPHP_RUNNING'] = 'true';
    $_SERVER['NATIVEPHP_RUNNING'] = 'true';

    expect(is_native_desktop())->toBeTrue()
        ->and(app_runtime())->toBe('desktop');

    $platform = require config_path('platform.php');

    expect($platform['runtime'])->toBe('desktop')
        ->and($platform['database_connection'])->toBe('sqlite');

    putenv('NATIVEPHP_RUNNING');
    unset($_ENV['NATIVEPHP_RUNNING'], $_SERVER['NATIVEPHP_RUNNING']);
});

test('native app service provider is registered in nativephp config', function () {
    expect(config('nativephp.provider'))->toBe(NativeAppServiceProvider::class);
});

test('native app url uses the embedded php server origin', function () {
    putenv('NATIVEPHP_RUNNING=true');
    $_ENV['NATIVEPHP_RUNNING'] = 'true';
    $_SERVER['NATIVEPHP_RUNNING'] = 'true';

    $request = Request::create('http://127.0.0.1:8123/_native/api/booted', 'POST');
    app()->instance('request', $request);

    expect(native_app_url('/'))->toBe('http://127.0.0.1:8123/')
        ->and(native_app_url('login'))->toBe('http://127.0.0.1:8123/login');

    putenv('NATIVEPHP_RUNNING');
    unset($_ENV['NATIVEPHP_RUNNING'], $_SERVER['NATIVEPHP_RUNNING']);
});

test('native desktop build assets exist for mac and windows', function () {
    expect(base_path('public/icon.png'))->toBeFile()
        ->and(base_path('public/icon.ico'))->toBeFile()
        ->and(base_path('public/icon.icns'))->toBeFile()
        ->and(base_path('scripts/build-native-mac.sh'))->toBeFile();
});
