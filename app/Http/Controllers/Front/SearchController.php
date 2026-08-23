<?php

declare(strict_types=1);

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Serves the public search page without coupling its Livewire state to another public page.
 *
 * The controller only normalizes initial input and selects the page; callers can rely on the
 * `q` parameter being capped before it is handed to the reactive results component.
 */
class SearchController extends Controller
{
    public function show(Request $request): View
    {
        $query = mb_substr(strip_tags($request->string('q')->trim()->toString()), 0, 100);

        return view('front.search', [
            'query' => $query,
        ]);
    }
}
