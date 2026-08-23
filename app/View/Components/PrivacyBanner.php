<?php

declare(strict_types=1);

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

final class PrivacyBanner extends Component
{
    public function __construct(public bool $shown = false) {}

    public function render(): View|Closure|string
    {
        return view('components.privacy-banner');
    }
}
