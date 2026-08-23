<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\AncestorsController;
use App\Http\Controllers\Api\V1\DescendantsController;
use App\Http\Controllers\Api\V1\LineageController;
use App\Http\Controllers\Api\V1\LineageMembersController;
use App\Http\Controllers\Api\V1\PersonController;
use App\Http\Controllers\Api\V1\SearchController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', fn (Request $request) => $request->user())->middleware('auth:sanctum');

Route::prefix('v1')->name('api.v1.')->group(function (): void {
    Route::get('persons/{person}', PersonController::class)->name('persons.show');
    Route::get('persons/{person}/descendants', DescendantsController::class)->name('persons.descendants');
    Route::get('persons/{person}/ancestors', AncestorsController::class)->name('persons.ancestors');

    Route::get('lineages/{lineage}', LineageController::class)->name('lineages.show');
    Route::get('lineages/{lineage}/members', LineageMembersController::class)->name('lineages.members');

    Route::get('search', SearchController::class)->name('search');
});
