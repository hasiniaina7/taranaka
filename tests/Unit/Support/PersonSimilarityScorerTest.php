<?php

declare(strict_types=1);

use App\Support\PersonCandidateInput;
use App\Support\PersonSimilarityScorer;

test('it combines normalized name similarity with birth year proximity', function (?int $candidateYear, float $expectedScore): void {
    $scorer = new PersonSimilarityScorer;

    expect($scorer->score(
        new PersonCandidateInput('Jean Rakoto', 1954),
        new PersonCandidateInput('  JEAN   RAKOTO  ', $candidateYear),
    ))->toBe($expectedScore);
})->with([
    'matching years'      => [1954, 1.0],
    'twenty years apart'  => [1974, 0.65],
    'forty years apart'   => [1994, 0.3],
    'one year is unknown' => [null, 0.65],
]);

test('a less similar name scores below an exact name with the same birth year', function (): void {
    $scorer = new PersonSimilarityScorer;
    $input  = new PersonCandidateInput('Jean Rakoto', 1954);

    expect($scorer->score($input, new PersonCandidateInput('Jeanne Rabe', 1954)))
        ->toBeLessThan($scorer->score($input, new PersonCandidateInput('Jean Rakoto', 1954)));
});
