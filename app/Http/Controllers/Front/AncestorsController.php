<?php

declare(strict_types=1);

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Person;
use Illuminate\View\View;

/**
 * Expose the public ancestor explorer through a thin HTTP boundary.
 *
 * The page orchestration lives here so the public route stays separate from
 * the authenticated people controller; callers can rely on route-model
 * binding to reject missing or soft-deleted people before Livewire mounts.
 */
class AncestorsController extends Controller
{
    public function __invoke(Person $person): View
    {
        return view('front.people.ancestors', [
            'person' => $person,
        ]);
    }
}
