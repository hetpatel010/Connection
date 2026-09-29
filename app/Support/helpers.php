<?php

use App\Application\Services\BusinessContext;

if (! function_exists('business_context')) {
    function business_context(): BusinessContext
    {
        return app(BusinessContext::class);
    }
}

if (! function_exists('branding')) {
    /**
     * @return ($key is null ? array<string, mixed> : mixed)
     */
    function branding(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return config('branding', []);
        }

        return data_get(config('branding', []), $key, $default);
    }
}

if (! function_exists('product_name')) {
    function product_name(): string
    {
        return (string) branding('product.name', config('app.name', 'Billing'));
    }
}

if (! function_exists('page_title')) {
    function page_title(?string $pageTitle = null): string
    {
        $appName = product_name();

        if ($pageTitle === null || trim($pageTitle) === '') {
            return $appName;
        }

        return trim($pageTitle).' — '.$appName;
    }
}

if (! function_exists('asset_url')) {
    function asset_url(?string $path): ?string
    {
        if ($path === null || trim($path) === '') {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return url($path);
    }
}

if (! function_exists('is_native_desktop')) {
    function is_native_desktop(): bool
    {
        return filter_var(env('NATIVEPHP_RUNNING', false), FILTER_VALIDATE_BOOL);
    }
}

if (! function_exists('app_runtime')) {
    function app_runtime(): string
    {
        return is_native_desktop() || env('APP_RUNTIME') === 'desktop'
            ? 'desktop'
            : (string) env('APP_RUNTIME', 'web');
    }
}

if (! function_exists('native_app_url')) {
    function native_app_url(string $path = '/'): string
    {
        if (! is_native_desktop()) {
            return url($path);
        }

        $rootUrl = rtrim(request()->getSchemeAndHttpHost(), '/');
        $path = '/'.ltrim($path, '/');

        return $rootUrl.$path;
    }
}
