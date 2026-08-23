<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $author_id
 * @property string $target_type
 * @property int|null $target_id
 * @property string|null $field
 * @property string|null $old_value
 * @property string $new_value
 * @property string|null $justification
 * @property string $status
 * @property int|null $reviewer_id
 * @property \Carbon\CarbonImmutable|null $reviewed_at
 * @property string|null $rejection_reason
 */
final class Contribution extends Model
{
    /** @use HasFactory<\Database\Factories\ContributionFactory> */
    use HasFactory;

    public const string STATUS_PENDING = 'pending';

    public const string STATUS_ACCEPTED = 'accepted';

    public const string STATUS_REJECTED = 'rejected';

    public const string TARGET_PERSON = 'person';

    public const string TARGET_COUPLE = 'couple';

    public const string TARGET_PERSON_NEW = 'person_new';

    public const string TARGET_RELATIONSHIP_NEW = 'relationship_new';

    protected $fillable = [
        'author_id',
        'target_type',
        'target_id',
        'field',
        'old_value',
        'new_value',
        'justification',
        'status',
        'reviewer_id',
        'reviewed_at',
        'rejection_reason',
    ];

    /* -------------------------------------------------------------------------------------------- */
    // Relations
    /* -------------------------------------------------------------------------------------------- */
    /**
     * @return BelongsTo<User, covariant Contribution>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<User, covariant Contribution>
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Resolve the target model this contribution proposes a change to.
     *
     * Not implemented as `morphTo()` because `target_type` includes
     * non-model states (`person_new`, `relationship_new`) that have no
     * target row until acceptance (data-model.md). Bypasses the `team`
     * global scope, since a contribution's target is routinely outside the
     * current viewer's team (that is this feature's reason to exist).
     */
    public function target(): Person|Couple|null
    {
        if ($this->target_id === null) {
            return null;
        }

        return match ($this->target_type) {
            self::TARGET_PERSON => Person::withoutGlobalScope('team')->find($this->target_id),
            self::TARGET_COUPLE => Couple::withoutGlobalScope('team')->find($this->target_id),
            default             => null,
        };
    }

    /**
     * Whether the target still exists and still carries the proposed
     * field's old value, computed at render time rather than a stored
     * column (data-model.md).
     */
    public function isStillApplicable(): bool
    {
        $target = $this->target();

        if ($target === null) {
            return in_array($this->target_type, [self::TARGET_PERSON_NEW, self::TARGET_RELATIONSHIP_NEW], true);
        }

        if ($this->field === null) {
            return true;
        }

        return (string) $target->getAttribute($this->field) === (string) $this->old_value;
    }

    protected function casts(): array
    {
        return [
            'reviewed_at' => 'datetime',
        ];
    }
}
