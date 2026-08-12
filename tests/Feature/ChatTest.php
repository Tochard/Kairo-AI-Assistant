<?php

use App\Ai\Agents\Kairo;
use App\Models\User;
use Illuminate\Support\Facades\RateLimiter;
use Laravel\Ai\Models\Conversation;
use Livewire\Livewire;

test('guests are redirected away from chat', function () {
    $this->get(route('chat'))->assertRedirect(route('login'));
});

test('a bare chat visit starts with a blank composer', function () {
    $user = User::factory()->create();

    Kairo::fake(['Earlier reply']);
    Livewire::actingAs($user)->test('pages::chat')->call('send', 'An earlier message');

    // The sidebar legitimately still shows the earlier conversation's title (echoes the
    // prompt) — what must NOT appear on a bare /chat visit is the reply bubble itself.
    $this->actingAs($user)
        ->get(route('chat'))
        ->assertDontSee('Earlier reply');
});

test('a user can send a message and see Kairos reply', function () {
    $user = User::factory()->create();

    Kairo::fake(['Nice, tell me more about that!']);

    Livewire::actingAs($user)
        ->test('pages::chat')
        ->call('send', 'I want to save ₦500k by December')
        ->assertSet('error', null);

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

test('sending twice on a bare chat continues the same new conversation', function () {
    $user = User::factory()->create();

    Kairo::fake(['First reply', 'Second reply']);

    $component = Livewire::actingAs($user)->test('pages::chat');

    $component->call('send', 'First message');
    $firstConversationId = $component->conversationId;

    $component->call('send', 'Second message');
    $secondConversationId = $component->conversationId;

    expect($firstConversationId)->not->toBeNull();
    expect($secondConversationId)->toBe($firstConversationId);
    expect(Conversation::count())->toBe(1);
});

test('visiting a specific conversation continues it', function () {
    $user = User::factory()->create();

    Kairo::fake(['Reply one']);
    $component = Livewire::actingAs($user)->test('pages::chat');
    $component->call('send', 'Message one');
    $conversationId = $component->conversationId;

    Kairo::fake(['Reply two']);
    Livewire::actingAs($user)
        ->test('pages::chat', ['conversation' => $conversationId])
        ->assertSet('conversationId', $conversationId)
        ->call('send', 'Message two');

    expect(Conversation::count())->toBe(1);
    Kairo::assertPrompted('Message two');
});

test('a user cannot open another users conversation', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    Kairo::fake(['A private reply']);
    $component = Livewire::actingAs($otherUser)->test('pages::chat');
    $component->call('send', 'A private message');
    $conversationId = $component->conversationId;

    $this->actingAs($user)
        ->get(route('chat', $conversationId))
        ->assertNotFound();
});

test('an empty or whitespace-only message is a no-op', function () {
    $user = User::factory()->create();

    Kairo::fake();

    Livewire::actingAs($user)
        ->test('pages::chat')
        ->call('send', '   ');

    Kairo::assertNeverPrompted();
});

test('an overly long message is rejected with a friendly error', function () {
    $user = User::factory()->create();

    Kairo::fake();

    Livewire::actingAs($user)
        ->test('pages::chat')
        ->call('send', str_repeat('a', 2001))
        ->assertSet('error', "That's a bit long for me to take in at once — mind trimming it down?");

    Kairo::assertNeverPrompted();
});

test('a user only sees their own conversations in the sidebar', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    Kairo::fake(['Reply to user one']);
    Livewire::actingAs($user)->test('pages::chat')->call('send', 'Hello from user one');

    Kairo::fake(['Reply to user two']);
    Livewire::actingAs($otherUser)->test('pages::chat')->call('send', 'Hello from user two');

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
        ->call('send', 'One more message')
        ->assertSet('error', "Let's take a short breather — mind trying again in a few minutes?");

    Kairo::assertNeverPrompted();
});
