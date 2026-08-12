<?php

use App\Ai\Agents\Kairo;
use App\Models\User;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;

test('guests are redirected away from chat', function () {
    $this->get(route('chat'))->assertRedirect(route('login'));
});

test('a user can send a message and see Kairos reply', function () {
    $user = User::factory()->create();

    Kairo::fake(['Nice, tell me more about that!']);

    Livewire::actingAs($user)
        ->test('pages::chat')
        ->set('message', 'I want to save ₦500k by December')
        ->call('send')
        ->assertSet('message', '');

    Kairo::assertPrompted('I want to save ₦500k by December');

    $this->assertDatabaseHas('agent_conversation_messages', [
        'role' => 'user',
        'content' => 'I want to save ₦500k by December',
    ]);

    $this->assertDatabaseHas('agent_conversation_messages', [
        'role' => 'assistant',
        'content' => 'Nice, tell me more about that!',
    ]);
});

test('an empty message cannot be sent', function () {
    $user = User::factory()->create();

    Kairo::fake();

    Livewire::actingAs($user)
        ->test('pages::chat')
        ->set('message', '')
        ->call('send')
        ->assertHasErrors('message');

    Kairo::assertNeverPrompted();
});

test('a user only sees their own conversation', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    Kairo::fake(['Reply to user one']);
    Livewire::actingAs($user)->test('pages::chat')->set('message', 'Hello from user one')->call('send');

    Kairo::fake(['Reply to user two']);
    Livewire::actingAs($otherUser)->test('pages::chat')->set('message', 'Hello from user two')->call('send');

    $this->actingAs($user)
        ->get(route('chat'))
        ->assertSee('Hello from user one')
        ->assertDontSee('Hello from user two');
});

test('chat is rate limited per user', function () {
    $user = User::factory()->create();

    Kairo::fake(['Response']);

    RateLimiter::clear('kairo-chat:'.$user->id);

    for ($i = 0; $i < 20; $i++) {
        RateLimiter::hit('kairo-chat:'.$user->id, 3600);
    }

    Livewire::actingAs($user)
        ->test('pages::chat')
        ->set('message', 'One more message')
        ->call('send')
        ->assertSet('error', "Let's take a short breather — mind trying again in a few minutes?");

    Kairo::assertNeverPrompted();
});
