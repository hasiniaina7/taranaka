<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property string|null $origin
 * @property string|null $cover_image
 * @property string $status
 * @property-read \Illuminate\Support\Collection<int, Person> $people
 */
final class Lineage extends Model
{
    /** @use HasFactory<\Database\Factories\LineageFactory> */
    use HasFactory;

    use LogsActivity;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'slug',
        'description',
        'origin',
        'cover_image',
        'status',
    ];

    /**
     * Generate a unique slug from the given name, appending a numeric suffix
     * (`-2`, `-3`, ...) on collision.
     */
    public static function uniqueSlugFor(string $name): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $i    = 2;

        while (self::query()->where('slug', $slug)->exists()) {
            $slug = $base . '-' . $i;
            $i++;
        }

        return $slug;
    }

    /* -------------------------------------------------------------------------------------------- */
    // Relations
    /* -------------------------------------------------------------------------------------------- */
    /** @return BelongsToMany<Person, $this, LineageMembership, 'pivot'> */
    public function people(): BelongsToMany
    {
        return $this->belongsToMany(Person::class, 'lineage_person')
            ->using(LineageMembership::class)
            ->withTimestamps();
    }

    /* -------------------------------------------------------------------------------------------- */
    public function isDeletable(): bool
    {
        return ! $this->people()->exists();
    }

    /* -------------------------------------------------------------------------------------------- */
    // Log activities
    /* -------------------------------------------------------------------------------------------- */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('lineage')
            ->setDescriptionForEvent(fn (string $eventName): string => __('lineage.lineage') . ' ' . __('app.event_' . $eventName))
            ->logOnly([
                'name',
                'description',
                'origin',
                'status',
            ])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    public function tapActivity(Activity $activity, string $eventName): void
    {
        $user = auth()->user();

        $activity->team_id = $user?->currentTeam?->id;
    }

    /* -------------------------------------------------------------------------------------------- */
    // Model events
    /* -------------------------------------------------------------------------------------------- */
    protected static function booted(): void
    {
        self::creating(function (Lineage $lineage): void {
            if (! $lineage->slug) {
                $lineage->slug = self::uniqueSlugFor($lineage->name);
            }
        });
    }
}
