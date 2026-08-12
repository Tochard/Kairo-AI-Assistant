<?php

use App\Ai\Agents\Kairo;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts::app')] class extends Component
{
    public ?string $error = null;

    public function send(string $text): ?string
    {
        $text = trim($text);

        if ($text === '') {
            return null;
        }

        if (mb_strlen($text) > 2000) {
            $this->error = "That's a bit long for me to take in at once — mind trimming it down?";

            return null;
        }

        $key = 'kairo-chat:'.auth()->id();

        if (RateLimiter::tooManyAttempts($key, 20)) {
            $this->error = "Let's take a short breather — mind trying again in a few minutes?";

            return null;
        }

        RateLimiter::hit($key, 3600);

        $this->error = null;

        try {
            $response = (new Kairo(auth()->user()))
                ->continueLastConversation(auth()->user())
                ->prompt($text);

            return $response->text;
        } catch (\Throwable $e) {
            report($e);

            $this->error = "I couldn't get a response just now — mind trying again in a moment?";

            return null;
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function with(): array
    {
        $conversation = auth()->user()->conversations()->latest('updated_at')->first();

        $messages = $conversation
            ? $conversation->messages()
                ->whereIn('role', ['user', 'assistant'])
                ->where('content', '!=', '')
                ->orderBy('created_at')
                ->get()
            : collect();

        return ['messages' => $messages];
    }
};
?>

<div
    class="flex flex-col gap-4"
    x-data="{ pending: [] }"
    x-init="$nextTick(() => window.scrollTo(0, document.body.scrollHeight))"
>
    <div>
        <h1 class="text-2xl font-bold tracking-tight text-ink">Kairo</h1>
        <p class="mt-1 text-sm text-ink-muted">Tell me what you're working toward.</p>
    </div>

    <div class="flex flex-col gap-3">
        @if ($messages->isEmpty())
            <div x-show="pending.length === 0" class="rounded-3xl border border-dashed border-border bg-surface p-8 text-center">
                <p class="text-ink-muted">Hey, I'm Kairo 👋 Tell me something you're working toward, and I'll help you plan it out.</p>
            </div>
        @endif

        @foreach ($messages as $msg)
            <div class="flex {{ $msg->role === 'user' ? 'justify-end' : 'justify-start' }}">
                <div
                    class="max-w-[85%] rounded-2xl px-4 py-2.5 sm:max-w-[75%] {{ $msg->role === 'user'
                        ? 'bg-accent text-accent-foreground'
                        : 'border border-border bg-surface text-ink' }}"
                >
                    <p class="text-sm whitespace-pre-wrap">{{ $msg->content }}</p>
                </div>
            </div>
        @endforeach

        <template x-for="p in pending" :key="p.id">
            <div class="flex justify-end">
                <div class="max-w-[85%] rounded-2xl bg-accent px-4 py-2.5 text-accent-foreground sm:max-w-[75%]">
                    <p class="text-sm whitespace-pre-wrap" x-text="p.text"></p>
                </div>
            </div>
        </template>

        <div x-show="pending.length > 0" x-cloak class="flex justify-start">
            <div class="flex items-center gap-1.5 rounded-2xl border border-border bg-surface px-4 py-3">
                <span class="size-1.5 animate-bounce rounded-full bg-ink-muted [animation-delay:-0.3s]"></span>
                <span class="size-1.5 animate-bounce rounded-full bg-ink-muted [animation-delay:-0.15s]"></span>
                <span class="size-1.5 animate-bounce rounded-full bg-ink-muted"></span>
            </div>
        </div>
    </div>

    @if ($error)
        <p class="text-sm text-red-400">{{ $error }}</p>
    @endif

    <form
        x-on:submit.prevent="
            const text = $refs.input.value.trim();
            if (! text || pending.length > 0) return;
            const id = Date.now() + Math.random();
            pending.push({ id, text });
            $refs.input.value = '';
            $nextTick(() => window.scrollTo(0, document.body.scrollHeight));
            $wire.send(text).then(() => {
                pending = pending.filter((p) => p.id !== id);
                $nextTick(() => window.scrollTo(0, document.body.scrollHeight));
            });
        "
        class="sticky bottom-20 z-20 flex items-center gap-2 md:bottom-6"
    >
        <input
            x-ref="input"
            type="text"
            placeholder="Message Kairo..."
            autocomplete="off"
            class="flex-1 rounded-full border border-border bg-surface px-4 py-3 text-ink shadow-lg placeholder:text-ink-muted focus:border-teal focus:ring-2 focus:ring-teal/30 focus:outline-none"
        >
        <button
            type="submit"
            :disabled="pending.length > 0"
            class="flex size-12 shrink-0 cursor-pointer items-center justify-center rounded-full bg-accent text-accent-foreground shadow-lg shadow-teal/20 transition hover:brightness-110 disabled:cursor-not-allowed disabled:opacity-50"
            aria-label="Send message"
        >
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-5">
                <path d="M5 12h14M13 6l6 6-6 6" />
            </svg>
        </button>
    </form>
</div>
