<?php

use App\Models\Trackable;
use App\Models\TrackableLog;
use App\Models\User;
use Livewire\Livewire;

test('a user can view their own trackable but not another users', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    $trackable = Trackable::factory()->for($user)->create();
    $othersTrackable = Trackable::factory()->for($otherUser)->create();

    $this->actingAs($user)
        ->get(route('trackables.show', $trackable))
        ->assertOk();

    Livewire::actingAs($user)
        ->test('pages::trackables.show', ['trackable' => $othersTrackable])
        ->assertForbidden();
});

test('a user can log a value against their own trackable', function () {
    $user = User::factory()->create();
    $trackable = Trackable::factory()->for($user)->create(['unit' => 'kg']);

    Livewire::actingAs($user)
        ->test('pages::trackables.show', ['trackable' => $trackable])
        ->set('value', '72.5')
        ->set('note', 'morning weigh-in')
        ->set('logged_at', now()->format('Y-m-d\TH:i'))
        ->call('log');

    $this->assertDatabaseHas('trackable_logs', [
        'trackable_id' => $trackable->id,
        'value' => 72.5,
        'note' => 'morning weigh-in',
    ]);
});

test('logging a value requires a numeric value', function () {
    $user = User::factory()->create();
    $trackable = Trackable::factory()->for($user)->create();

    Livewire::actingAs($user)
        ->test('pages::trackables.show', ['trackable' => $trackable])
        ->set('value', 'not-a-number')
        ->call('log')
        ->assertHasErrors('value');
});

test('a user can delete a log entry from their own trackable', function () {
    $user = User::factory()->create();
    $trackable = Trackable::factory()->for($user)->create();
    $log = TrackableLog::factory()->for($trackable)->create();

    Livewire::actingAs($user)
        ->test('pages::trackables.show', ['trackable' => $trackable])
        ->call('deleteLog', $log->id);

    $this->assertModelMissing($log);
});

test('a user cannot delete a log entry belonging to another users trackable', function () {
    $user = User::factory()->create();
    $trackable = Trackable::factory()->for($user)->create();

    $otherUser = User::factory()->create();
    $othersTrackable = Trackable::factory()->for($otherUser)->create();
    $othersLog = TrackableLog::factory()->for($othersTrackable)->create();

    Livewire::actingAs($user)
        ->test('pages::trackables.show', ['trackable' => $trackable])
        ->call('deleteLog', $othersLog->id)
        ->assertForbidden();

    $this->assertModelExists($othersLog);
});
