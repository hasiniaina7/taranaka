<?php

declare(strict_types=1);

use App\Actions\Search\FindPublicPeople;
use App\Models\Person;
use Illuminate\Support\Facades\DB;

test('public people projection does not hydrate unrelated family graph relations per result', function (): void {
    Person::factory()->count(10)->create([
        'firstname' => 'Efficientneedle',
        'yod'       => 2000,
    ]);

    DB::flushQueryLog();
    DB::enableQueryLog();

    $results = app(FindPublicPeople::class)->limited('Efficientneedle', 10);

    expect($results)->toHaveCount(10);
    expect(DB::getQueryLog())->toHaveCount(2);
});
