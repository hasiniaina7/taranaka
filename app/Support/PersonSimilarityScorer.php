<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Scores how likely two lightweight person identities describe the same individual.
 *
 * The formula is isolated from candidate retrieval so it remains a pure, tuneable contract:
 * callers always receive a normalized score between zero and one.
 */
class PersonSimilarityScorer
{
    public const float DISPLAY_THRESHOLD = 0.4;

    public const float HIGH_CONFIDENCE_THRESHOLD = 0.75;

    public function score(PersonCandidateInput $first, PersonCandidateInput $second): float
    {
        $score = ($this->nameScore($first->name, $second->name) * 0.3)
            + ($this->birthYearScore($first->birthYear, $second->birthYear) * 0.7);

        return round(max(0.0, min(1.0, $score)), 4);
    }

    protected function nameScore(string $first, string $second): float
    {
        $first  = $this->normalizeName($first);
        $second = $this->normalizeName($second);

        if ($first === '' || $second === '') {
            return 0.0;
        }

        if ($first === $second) {
            return 1.0;
        }

        $maximumLength = max(mb_strlen($first), mb_strlen($second));

        if ($maximumLength === 0) {
            return 0.0;
        }

        return max(0.0, 1 - (levenshtein($first, $second) / $maximumLength));
    }

    protected function birthYearScore(?int $first, ?int $second): float
    {
        if ($first === null || $second === null) {
            return 0.5;
        }

        return max(0.0, 1 - (abs($first - $second) / 40));
    }

    protected function normalizeName(string $name): string
    {
        return Str::of(strip_tags($name))->ascii()->lower()->squish()->toString();
    }
}
