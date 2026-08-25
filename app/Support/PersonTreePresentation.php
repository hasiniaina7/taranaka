<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Person;
use Illuminate\Support\Facades\Storage;

/**
 * Shared photo/partner projection for the public descendant and ancestor
 * tree canvases (spec 012). Reuses the existing photo disk convention from
 * resources/views/components/tree-node/*.blade.php rather than introducing
 * a second one.
 */
final class PersonTreePresentation
{
    public static function photoUrl(Person $person): ?string
    {
        if ($person->photo === null || $person->photo === '') {
            return null;
        }

        if (! PersonPrivacy::isPubliclyVisible($person)) {
            return null;
        }

        $photoPath = "{$person->team_id}/{$person->id}/{$person->photo}_small.webp";

        if (! Storage::disk('photos')->exists($photoPath)) {
            return null;
        }

        return Storage::disk('photos')->url($photoPath);
    }

    /** @return list<array{id: int, name: string, photo_url: string|null}> */
    public static function partners(Person $person): array
    {
        return $person->couples()
            ->get()
            ->map(function ($couple) use ($person) {
                $partner = $couple->person1_id === $person->id ? $couple->person2 : $couple->person1;

                return $partner instanceof Person ? $partner : null;
            })
            ->filter()
            ->unique('id')
            ->map(fn (Person $partner): array => [
                'id'        => $partner->id,
                'name'      => $partner->name,
                'photo_url' => self::photoUrl($partner),
            ])
            ->values()
            ->all();
    }
}
