<?php

use App\Enums\TrackableDirection;
use App\Models\Goal;
use App\Models\Trackable;
use App\Models\User;
use Livewire\Livewire;

test('a user can see their own trackables but not another users', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    Trackable::factory()->for($user)->create(['name' => 'My savings']);
    Trackable::factory()->for($otherUser)->create(['name' => 'Not my savings']);

    $this->actingAs($user)
        ->get(route('trackables.index'))
        ->assertSee('My savings')
        ->assertDontSee('Not my savings');
});

test('a user can create a trackable optionally linked to their own goal', function () {
    $user = User::factory()->create();
    $goal = Goal::factory()->for($user)->create();

    Livewire::actingAs($user)
        ->test('pages::trackables.create')
        ->set('name', 'Savings')
        ->set('unit', '₦')
        ->set('direction', TrackableDirection::UpGood->value)
        ->set('target_value', '500000')
        ->set('goal_id', $goal->id)
        ->call('save')
        ->assertRedirect(route('trackables.index'));

    $this->assertDatabaseHas('trackables', [
        'user_id' => $user->id,
        'goal_id' => $goal->id,
        'name' => 'Savings',
        'unit' => '₦',
        'direction' => TrackableDirection::UpGood->value,
    ]);
});

test('a trackable requires a name and unit', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test('pages::trackables.create')
        ->set('name', '')
        ->set('unit', '')
        ->call('save')
        ->assertHasErrors(['name', 'unit']);
});

test('a user cannot link a trackable to another users goal', function () {
    $user = User::factory()->create();
    $otherUsersGoal = Goal::factory()->create();

    Livewire::actingAs($user)
        ->test('pages::trackables.create')
        ->set('name', 'Sneaky trackable')
        ->set('unit', 'kg')
        ->set('goal_id', $otherUsersGoal->id)
        ->call('save')
        ->assertHasErrors('goal_id');

    $this->assertDatabaseMissing('trackables', ['name' => 'Sneaky trackable']);
});

test('a user can update their own trackable', function () {
    $user = User::factory()->create();
    $trackable = Trackable::factory()->for($user)->create();

    Livewire::actingAs($user)
        ->test('pages::trackables.edit', ['trackable' => $trackable])
        ->set('name', 'Updated name')
        ->set('direction', TrackableDirection::DownGood->value)
        ->call('save')
        ->assertRedirect(route('trackables.index'));

    expect($trackable->fresh()->name)->toBe('Updated name');
    expect($trackable->fresh()->direction)->toBe(TrackableDirection::DownGood);
});

test('a user cannot edit another users trackable', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $trackable = Trackable::factory()->for($otherUser)->create();

    Livewire::actingAs($user)
        ->test('pages::trackables.edit', ['trackable' => $trackable])
        ->assertForbidden();
});

test('a user can delete their own trackable', function () {
    $user = User::factory()->create();
    $trackable = Trackable::factory()->for($user)->create();

    Livewire::actingAs($user)
        ->test('pages::trackables.index')
        ->call('delete', $trackable->id);

    $this->assertModelMissing($trackable);
});

test('a user cannot delete another users trackable', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $trackable = Trackable::factory()->for($otherUser)->create();

    Livewire::actingAs($user)
        ->test('pages::trackables.index')
        ->call('delete', $trackable->id)
        ->assertForbidden();

    $this->assertModelExists($trackable);
});
