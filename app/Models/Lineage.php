<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * Represents a publicly discoverable family lineage and its person memberships.
 *
 * Search belongs on the model because callers rely on one consistently escaped
 * name-matching contract across the directory and global search entry points.
 *
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property string|null $origin
 * @property string|null $cover_image
 * @property string $status
 * @property-read \Illuminate\Support\Collection<int, Person> $people
 */
class Lineage extends Model
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

    /** @param Builder<self> $query */
    #[Scope]
    public function scopeSearch(Builder $query, string $searchString): void
    {
        $searchString = strip_tags(mb_trim($searchString));

        if ($searchString === '' || $searchString === '%') {
            return;
        }

        $escapedSearch = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $searchString);

        $query->where('name', 'like', '%' . $escapedSearch . '%');
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
