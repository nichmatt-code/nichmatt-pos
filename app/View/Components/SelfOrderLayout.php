<?php

namespace App\View\Components;

use App\Models\Store;
use Illuminate\View\Component;
use Illuminate\View\View;

class SelfOrderLayout extends Component
{
    public function __construct(public Store $store) {}

    /**
     * Get the view / contents that represents the component.
     */
    public function render(): View
    {
        return view('layouts.self-order');
    }
}
