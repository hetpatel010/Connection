<?php

namespace App\Providers;

use Illuminate\Support\Facades\URL;
use Native\Desktop\Contracts\ProvidesPhpIni;
use Native\Desktop\Facades\Window;

class NativeAppServiceProvider implements ProvidesPhpIni
{
    /**
     * Executed once the native application has been booted.
     */
    public function boot(): void
    {
        URL::forceRootUrl(request()->getSchemeAndHttpHost());

        Window::open()
            ->title(product_name())
            ->url(native_app_url('/'))
            ->width(1440)
            ->height(900)
            ->minWidth(1024)
            ->minHeight(640);
    }

    /**
     * @return array<string, string>
     */
    public function phpIni(): array
    {
        return [
            'memory_limit' => '512M',
            'max_execution_time' => '0',
            'max_input_time' => '0',
            'upload_max_filesize' => '10M',
            'post_max_size' => '12M',
        ];
    }
}
