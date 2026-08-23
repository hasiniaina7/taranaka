<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Carries only the identity signals required to compare two people.
 *
 * Keeping this value separate from Eloquent makes similarity scoring deterministic and usable
 * without a database. Callers may rely on the name being raw input; the scorer normalizes it.
 */
class PersonCandidateInput
{
    public function __construct(
        public readonly string $name,
        public readonly ?int $birthYear,
    ) {}
}
