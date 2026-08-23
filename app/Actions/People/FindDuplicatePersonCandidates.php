<?php

declare(strict_types=1);

namespace App\Actions\People;

use App\Models\Person;
use App\Support\PersonCandidateInput;
use App\Support\PersonPrivacy;
use App\Support\PersonSimilarityScorer;
use Illuminate\Support\Collection;

/**
 * Finds and safely projects likely duplicates for the interactive person-creation flow.
 *
 * This action owns the deliberate cross-team scope bypass, bounded candidate pool, scoring policy,
 * and privacy projection so UI callers cannot accidentally diverge on any of those contracts.
 */
class FindDuplicatePersonCandidates
{
    public const int CANDIDATE_POOL_LIMIT = 100;

    public const int RESULT_LIMIT = 5;

    public const int MINIMUM_NAME_LENGTH = 3;

    public const int DISTANT_BIRTH_YEAR_GAP = 20;

    /**
     * @param  array<int, string|null>  $nameFields
     * @return list<array{id: int, name: string, lifespan: ?string, lineages: list<string>, private: bool, score: float, percentage: int, high_confidence: bool, url: string}>
     */
    public function execute(array $nameFields, ?int $birthYear): array
    {
        $terms = collect($nameFields)
            ->map(fn (?string $name): string => mb_trim(strip_tags((string) $name)))
            ->filter(fn (string $name): bool => mb_strlen($name) >= self::MINIMUM_NAME_LENGTH)
            ->values();

        if ($terms->isEmpty()) {
            return [];
        }

        $input = new PersonCandidateInput($this->primaryName($nameFields), $birthYear);

        $ranked = Person::withoutGlobalScope('team')
            ->with('lineages:id,name')
            ->similarTo(null, $terms->all())
            ->limit(self::CANDIDATE_POOL_LIMIT)
            ->get()
            ->map(function (Person $person) use ($input): array {
                $candidateYear = $person->birthYear === null ? null : (int) $person->birthYear;
                $score         = app(PersonSimilarityScorer::class)->score(
                    $input,
                    new PersonCandidateInput($person->name, $candidateYear),
                );

                return [
                    'person'         => $person,
                    'score'          => $score,
                    'candidate_year' => $candidateYear,
                    'input_year'     => $input->birthYear,
                ];
            })
            ->filter(fn (array $candidate): bool => $this->shouldDisplay($candidate))
            ->sortByDesc('score')
            ->take(self::RESULT_LIMIT)
            ->values();

        $accessibleIds = $this->accessibleCandidateIds($ranked);

        return array_values($ranked
            ->map(fn (array $candidate): array => $this->project(
                $candidate['person'],
                $candidate['score'],
                $accessibleIds,
            ))
            ->all());
    }

    /** @param array<int, string|null> $nameFields */
    protected function primaryName(array $nameFields): string
    {
        $primaryName = mb_trim(implode(' ', array_filter([
            $nameFields[0] ?? null,
            $nameFields[1] ?? null,
        ])));

        if ($primaryName !== '') {
            return $primaryName;
        }

        return mb_trim(implode(' ', array_filter($nameFields)));
    }

    /** @param array{score: float, candidate_year: ?int, input_year: ?int} $candidate */
    protected function shouldDisplay(array $candidate): bool
    {
        if ($candidate['score'] < PersonSimilarityScorer::DISPLAY_THRESHOLD) {
            return false;
        }

        if ($candidate['candidate_year'] === null || $candidate['input_year'] === null) {
            return true;
        }

        return abs($candidate['candidate_year'] - $candidate['input_year']) < self::DISTANT_BIRTH_YEAR_GAP;
    }

    /**
     * @param  Collection<int, array{person: Person, score: float, candidate_year: int|null, input_year: int|null}>  $ranked
     * @return list<int>
     */
    protected function accessibleCandidateIds(Collection $ranked): array
    {
        return array_values(Person::query()
            ->whereKey($ranked->pluck('person.id')->all())
            ->pluck('id')
            ->map(fn (int|string $id): int => (int) $id)
            ->all());
    }

    /**
     * @param  list<int>  $accessibleIds
     * @return array{id: int, name: string, lifespan: ?string, lineages: list<string>, private: bool, score: float, percentage: int, high_confidence: bool, url: string}
     */
    protected function project(Person $person, float $score, array $accessibleIds): array
    {
        $private = ! PersonPrivacy::isPubliclyVisible($person);

        return [
            'id'       => $person->id,
            'name'     => $person->name,
            'lifespan' => $private ? null : $person->lifetime,
            'lineages' => $private ? [] : array_values($person->lineages
                ->pluck('name')
                ->map(fn (mixed $name): string => (string) $name)
                ->all()),
            'private'         => $private,
            'score'           => $score,
            'percentage'      => (int) round($score * 100),
            'high_confidence' => $score >= PersonSimilarityScorer::HIGH_CONFIDENCE_THRESHOLD,
            'url'             => in_array($person->id, $accessibleIds, true)
                ? route('people.show', $person)
                : route('public.people.show', $person),
        ];
    }
}
