<?php

declare(strict_types=1);

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Person;
use Illuminate\Contracts\View\View;

/**
 * Present the public descendant explorer without inheriting back-office access rules.
 *
 * The public endpoint has its own controller because the existing descendant page is
 * authenticated and team-oriented. Callers receive the public explorer shell only.
 */
class DescendantsController extends Controller
{
    public function show(int $person): View
    {
        return view('front.descendants', [
            'person' => Person::withoutGlobalScope('team')->findOrFail($person),
        ]);
    }
}
