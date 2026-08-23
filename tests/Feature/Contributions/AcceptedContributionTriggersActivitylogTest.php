<?php

declare(strict_types=1);

use App\Models\Contribution;
use App\Models\Person;
use App\Models\User;
use Spatie\Activitylog\Models\Activity;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

test('accepting a contribution applies the change through the normal Eloquent path so Activitylog records it', function (): void {
    $owner     = User::factory()->withPersonalTeam()->create();
    $moderator = User::factory()->withPersonalTeam()->create(['is_moderator' => true]);
    $person    = Person::factory()->withUser($owner)->create(['surname' => 'Original']);

    $contribution = Contribution::factory()->pending()->create([
        'target_type' => Contribution::TARGET_PERSON,
        'target_id'   => $person->id,
        'field'       => 'surname',
        'old_value'   => 'Original',
        'new_value'   => 'Corrected',
    ]);

    $this->actingAs($moderator);

    $activityCountBefore = Activity::query()->where('subject_type', Person::class)->where('subject_id', $person->id)->count();

    Livewire\Livewire::test('contributions::review', ['contribution' => $contribution])
        ->call('accept');

    $activityCountAfter = Activity::query()->where('subject_type', Person::class)->where('subject_id', $person->id)->count();

    expect($activityCountAfter)->toBeGreaterThan($activityCountBefore);
});
