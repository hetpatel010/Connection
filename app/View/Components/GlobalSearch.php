<?php

namespace App\View\Components;

use App\Support\GlobalSearchRegistry;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class GlobalSearch extends Component
{
    /**
     * @var list<array{id: string, label: string, group: string, url: string, keywords: list<string>}>
     */
    public array $items;

    public function __construct()
    {
        $this->items = GlobalSearchRegistry::items();
    }

    public function render(): View|Closure|string
    {
        return view('components.global-search');
    }
}
