<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Contracts\DescendantsQueryInterface;
use App\Models\Person;
use Illuminate\Support\Collection;
use stdClass;

class FakeDescendantsQuery implements DescendantsQueryInterface
{
    /** @var list<array{person_id: int, max_depth: int}> */
    public array $calls = [];

    /** @param Collection<int, object> $rows */
    public function __construct(protected Collection $rows) {}

    public static function row(Person $person, int $degree, string $sequence): object
    {
        $row = new stdClass;

        foreach (['id', 'firstname', 'surname', 'sex', 'father_id', 'mother_id', 'dod', 'yod', 'team_id', 'photo', 'dob', 'yob'] as $attribute) {
            $row->{$attribute} = $person->{$attribute};
        }

        $row->degree   = $degree;
        $row->sequence = $sequence;

        return $row;
    }

    public function getDescendants(int $personId, int $maxDepth): Collection
    {
        $this->calls[] = ['person_id' => $personId, 'max_depth' => $maxDepth];

        return $this->rows
            ->filter(fn (object $row): bool => $row->degree <= $maxDepth)
            ->values();
    }
}
