<?php

use App\Enums\GoalStatus;
use App\Models\Goal;
use App\Models\User;
use Livewire\Livewire;

test('a user can see their own goals but not another users', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    Goal::factory()->for($user)->create(['title' => 'My goal']);
    Goal::factory()->for($otherUser)->create(['title' => 'Not my goal']);

    $this->actingAs($user)
        ->get(route('goals.index'))
        ->assertSee('My goal')
        ->assertDontSee('Not my goal');
});

test('a user can create a goal', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test('pages::goals.create')
        ->set('title', 'Save ₦500k by December')
        ->set('description', 'Emergency fund')
        ->set('target_date', '2026-12-31')
        ->call('save')
        ->assertRedirect(route('goals.index'));

    $this->assertDatabaseHas('goals', [
        'user_id' => $user->id,
        'title' => 'Save ₦500k by December',
        'status' => GoalStatus::Active->value,
    ]);
});

test('a goal requires a title', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test('pages::goals.create')
        ->set('title', '')
        ->call('save')
        ->assertHasErrors('title');
});

test('a user can update their own goal', function () {
    $user = User::factory()->create();
    $goal = Goal::factory()->for($user)->create();

    Livewire::actingAs($user)
        ->test('pages::goals.edit', ['goal' => $goal])
        ->set('title', 'Updated title')
        ->set('status', GoalStatus::Completed->value)
        ->call('save')
        ->assertRedirect(route('goals.index'));

    expect($goal->fresh()->title)->toBe('Updated title');
    expect($goal->fresh()->status)->toBe(GoalStatus::Completed);
});

test('a user cannot edit another users goal', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $goal = Goal::factory()->for($otherUser)->create();

    Livewire::actingAs($user)
        ->test('pages::goals.edit', ['goal' => $goal])
        ->assertForbidden();
});

test('a user can delete their own goal', function () {
    $user = User::factory()->create();
    $goal = Goal::factory()->for($user)->create();

    Livewire::actingAs($user)
        ->test('pages::goals.index')
        ->call('delete', $goal->id);

    $this->assertModelMissing($goal);
});

test('a user cannot delete another users goal', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $goal = Goal::factory()->for($otherUser)->create();

    Livewire::actingAs($user)
        ->test('pages::goals.index')
        ->call('delete', $goal->id)
        ->assertForbidden();

    $this->assertModelExists($goal);
});
