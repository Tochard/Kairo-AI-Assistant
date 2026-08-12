<?php

use App\Ai\Agents\Kairo;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

new #[Layout('layouts::app')] class extends Component
{
    #[Validate('required|string|max:2000')]
    public string $message = '';

    public ?string $error = null;

    public function send(): void
    {
        $this->validate();

        $key = 'kairo-chat:'.auth()->id();

        if (RateLimiter::tooManyAttempts($key, 20)) {
            $this->error = "Let's take a short breather — mind trying again in a few minutes?";

            return;
        }

        RateLimiter::hit($key, 3600);

        $this->error = null;
        $text = $this->message;
        $this->message = '';

        try {
            (new Kairo(auth()->user()))
                ->continueLastConversation(auth()->user())
                ->prompt($text);
        } catch (\Throwable $e) {
            report($e);

            $this->error = "I couldn't get a response just now — mind trying again in a moment?";
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
    x-data
    x-init="$nextTick(() => window.scrollTo(0, document.body.scrollHeight))"
>
    <div>
        <h1 class="text-2xl font-bold tracking-tight text-ink">Kairo</h1>
        <p class="mt-1 text-sm text-ink-muted">Tell me what you're working toward.</p>
    </div>

    <div class="flex flex-col gap-3">
        @forelse ($messages as $msg)
            <div class="flex {{ $msg->role === 'user' ? 'justify-end' : 'justify-start' }}">
                <div
                    class="max-w-[85%] rounded-2xl px-4 py-2.5 sm:max-w-[75%] {{ $msg->role === 'user'
                        ? 'bg-accent text-accent-foreground'
                        : 'border border-border bg-surface text-ink' }}"
                >
                    <p class="text-sm whitespace-pre-wrap">{{ $msg->content }}</p>
                </div>
            </div>
        @empty
            <div class="rounded-3xl border border-dashed border-border bg-surface p-8 text-center">
                <p class="text-ink-muted">Hey, I'm Kairo 👋 Tell me something you're working toward, and I'll help you plan it out.</p>
            </div>
        @endforelse

        <div wire:loading wire:target="send" class="flex justify-start">
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

    <form wire:submit="send" class="sticky bottom-20 z-20 flex items-center gap-2 md:bottom-6">
        <input
            wire:model="message"
            type="text"
            placeholder="Message Kairo..."
            autocomplete="off"
            class="flex-1 rounded-full border border-border bg-surface px-4 py-3 text-ink shadow-lg placeholder:text-ink-muted focus:border-teal focus:ring-2 focus:ring-teal/30 focus:outline-none"
        >
        <button
            type="submit"
            wire:loading.attr="disabled"
            wire:target="send"
            class="flex size-12 shrink-0 cursor-pointer items-center justify-center rounded-full bg-accent text-accent-foreground shadow-lg shadow-teal/20 transition hover:brightness-110 disabled:cursor-not-allowed disabled:opacity-50"
            aria-label="Send message"
        >
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-5">
                <path d="M5 12h14M13 6l6 6-6 6" />
            </svg>
        </button>
    </form>
    @error('message')
        <p class="text-sm text-red-400">{{ $message }}</p>
    @enderror
</div>
