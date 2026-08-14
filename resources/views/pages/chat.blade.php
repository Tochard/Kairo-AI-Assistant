<?php

use App\Ai\Agents\Kairo;
use App\Models\Goal;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Laravel\Ai\Approvals\Decision;
use Laravel\Ai\Approvals\Decisions;
use Laravel\Ai\Audio;
use Laravel\Ai\Enums\Lab;
use Laravel\Ai\Models\Conversation;
use Laravel\Ai\Models\ConversationMessage;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts::app')] class extends Component
{
    public ?string $conversationId = null;

    public ?string $error = null;

    public function mount(?string $conversation = null): void
    {
        if ($conversation === null) {
            return;
        }

        abort_unless($this->ownsConversation($conversation), 404);

        $this->conversationId = $conversation;
    }

    public function renameConversation(string $conversationId, string $title): void
    {
        abort_unless($this->ownsConversation($conversationId), 403);

        $title = trim($title);

        if ($title === '') {
            return;
        }

        Conversation::whereKey($conversationId)->update(['title' => Str::limit($title, 100, '')]);
    }

    protected function ownsConversation(string $conversationId): bool
    {
        $model = Conversation::find($conversationId);

        return $model
            && $model->participant_type === Conversation::participantType(auth()->user())
            && (string) $model->participant_id === (string) Conversation::participantKey(auth()->user());
    }

    /**
     * The single pending tool-call approval on this conversation, if any.
     *
     * @return array{callId: string, tool: string, arguments: array<string, mixed>, reason: ?string}|null
     */
    protected function pendingApproval(): ?array
    {
        if ($this->conversationId === null) {
            return null;
        }

        $message = ConversationMessage::query()
            ->where('conversation_id', $this->conversationId)
            ->where('role', 'assistant')
            ->whereNotNull('approval_state')
            ->latest('created_at')
            ->first();

        if (! $message) {
            return null;
        }

        $pending = $message->approval_state['pending'] ?? [];

        if (empty($pending)) {
            return null;
        }

        $callId = array_key_first($pending);

        $toolCall = collect($message->tool_calls ?? [])->firstWhere('id', $callId);

        if (! $toolCall) {
            return null;
        }

        return [
            'callId' => $callId,
            'tool' => $toolCall['name'],
            'arguments' => $toolCall['arguments'],
            'reason' => $pending[$callId],
        ];
    }

    /**
     * Resolve a pending tool-call approval and continue the conversation.
     *
     * @param  array<string, mixed>|null  $edits
     * @return array<string, mixed>
     */
    public function respondToApproval(string $callId, string $action, ?array $edits = null, ?string $feedback = null): array
    {
        $pending = $this->pendingApproval();

        if (! $pending || $pending['callId'] !== $callId) {
            $this->error = "That plan isn't waiting for a decision anymore.";

            return ['text' => null, 'conversationId' => $this->conversationId];
        }

        $decision = match ($action) {
            'approve' => Decision::approve(),
            'reject' => Decision::reject($feedback),
            'edit' => Decision::edit([...$pending['arguments'], ...($edits ?? [])]),
            default => null,
        };

        abort_if($decision === null, 422);

        $this->error = null;

        try {
            $response = (new Kairo(auth()->user()))
                ->continue($this->conversationId, as: auth()->user())
                ->prompt(Decisions::from([$callId => $decision]));

            return ['text' => $response->text, 'conversationId' => $this->conversationId];
        } catch (\Throwable $e) {
            report($e);

            $this->error = "I couldn't process that just now — mind trying again in a moment?";

            return ['text' => null, 'conversationId' => $this->conversationId];
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function send(string $text): array
    {
        $text = trim($text);

        if ($text === '') {
            return ['text' => null, 'conversationId' => $this->conversationId];
        }

        if (mb_strlen($text) > 2000) {
            $this->error = "That's a bit long for me to take in at once — mind trimming it down?";

            return ['text' => null, 'conversationId' => $this->conversationId];
        }

        $key = 'kairo-chat:'.auth()->id();

        if (RateLimiter::tooManyAttempts($key, 20)) {
            $this->error = "Let's take a short breather — mind trying again in a few minutes?";

            return ['text' => null, 'conversationId' => $this->conversationId];
        }

        RateLimiter::hit($key, 3600);

        $this->error = null;

        try {
            $agent = new Kairo(auth()->user());

            $agent = $this->conversationId
                ? $agent->continue($this->conversationId, as: auth()->user())
                : $agent->forUser(auth()->user());

            $response = $agent->prompt($text);

            $this->conversationId ??= $response->conversationId;

            return ['text' => $response->text, 'conversationId' => $this->conversationId];
        } catch (\Throwable $e) {
            report($e);

            $this->error = "I couldn't get a response just now — mind trying again in a moment?";

            return ['text' => null, 'conversationId' => $this->conversationId];
        }
    }

    /**
     * Synthesize speech for the given text via Gemini's TTS model.
     *
     * @return array{audio: ?string, mime: ?string}
     */
    public function speak(string $text): array
    {
        $text = trim($text);

        if ($text === '') {
            return ['audio' => null, 'mime' => null];
        }

        $key = 'kairo-speak:'.auth()->id();

        if (RateLimiter::tooManyAttempts($key, 30)) {
            return ['audio' => null, 'mime' => null];
        }

        RateLimiter::hit($key, 3600);

        try {
            $audio = Audio::of(Str::limit($text, 3000, ''))
                ->female()
                ->generate(provider: Lab::Gemini);

            return ['audio' => $audio->audio, 'mime' => $audio->mimeType()];
        } catch (\Throwable $e) {
            report($e);

            return ['audio' => null, 'mime' => null];
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function with(): array
    {
        $messages = $this->conversationId
            ? ConversationMessage::query()
                ->where('conversation_id', $this->conversationId)
                ->whereIn('role', ['user', 'assistant'])
                ->where('content', '!=', '')
                ->orderBy('created_at')
                ->get()
            : collect();

        $pendingApproval = $this->pendingApproval();

        return [
            'messages' => $messages,
            'conversations' => auth()->user()->conversations()->latest('updated_at')->get(),
            'pendingApproval' => $pendingApproval,
            'renegotiationGoal' => $pendingApproval && $pendingApproval['tool'] === 'RenegotiateCommitmentTool'
                ? Goal::find($pendingApproval['arguments']['goal_id'] ?? null)
                : null,
        ];
    }
};
?>

<div class="flex h-full items-stretch gap-6 px-4 pt-4 pb-2 md:px-6 md:pb-4" x-data="chatPage()">
    {{-- Desktop history sidebar --}}
    <aside class="no-scrollbar hidden h-full w-56 shrink-0 flex-col gap-1 overflow-y-auto md:flex">
        <a
            href="{{ route('chat') }}"
            wire:navigate
            class="mb-2 flex items-center gap-1.5 rounded-xl border border-border px-3 py-2 text-sm font-medium text-ink transition hover:border-teal hover:text-teal"
        >
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" class="size-4">
                <path d="M12 5v14M5 12h14" />
            </svg>
            New chat
        </a>

        <div class="flex flex-col gap-0.5">
            @foreach ($conversations as $conv)
                @include('pages.chat.conversation-item', ['conv' => $conv])
            @endforeach
        </div>
    </aside>

    {{-- Mobile history drawer --}}
    <div x-show="historyOpen" x-cloak class="fixed inset-0 z-50 md:hidden">
        <div class="absolute inset-0 bg-black/50" x-on:click="historyOpen = false"></div>
        <aside class="no-scrollbar relative flex h-full w-72 flex-col gap-1 overflow-y-auto bg-surface p-4 shadow-2xl" x-on:click.outside="historyOpen = false">
            <div class="mb-2 flex items-center justify-between">
                <span class="font-bold text-ink">Chats</span>
                <button type="button" x-on:click="historyOpen = false" aria-label="Close" class="flex size-11 cursor-pointer items-center justify-center rounded-full text-ink-muted hover:bg-surface-muted">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" class="size-4">
                        <path d="M6 6l12 12M18 6L6 18" />
                    </svg>
                </button>
            </div>

            <a
                href="{{ route('chat') }}"
                wire:navigate
                class="mb-2 flex min-h-11 items-center gap-1.5 rounded-xl border border-border px-3 py-2 text-sm font-medium text-ink"
            >
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" class="size-4">
                    <path d="M12 5v14M5 12h14" />
                </svg>
                New chat
            </a>

            @foreach ($conversations as $conv)
                @include('pages.chat.conversation-item', ['conv' => $conv])
            @endforeach
        </aside>
    </div>

    {{-- Main chat column --}}
    <div class="flex h-full min-w-0 flex-1 flex-col gap-3">
        <div class="flex shrink-0 items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-ink">Kairo</h1>
                <p class="mt-1 text-sm text-ink-muted">Tell me what you're working toward.</p>
            </div>
            <button
                type="button"
                x-on:click="historyOpen = true"
                aria-label="Chat history"
                class="flex size-11 shrink-0 cursor-pointer items-center justify-center rounded-full border border-border text-ink-muted transition hover:border-teal hover:text-teal md:hidden"
            >
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="size-5">
                    <circle cx="12" cy="12" r="9" />
                    <path d="M12 7v5l3 3" />
                </svg>
            </button>
        </div>

        {{-- Scrollable message area --}}
        <div x-ref="messageList" class="no-scrollbar flex min-h-0 flex-1 flex-col gap-3 overflow-y-auto">
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
                        @if ($msg->role === 'assistant')
                            <div class="chat-markdown text-sm">{!! Str::markdown($msg->content, ['html_input' => 'strip', 'allow_unsafe_links' => false]) !!}</div>
                        @else
                            <p class="text-sm whitespace-pre-wrap">{{ $msg->content }}</p>
                        @endif
                    </div>
                </div>

                @if ($msg->role === 'assistant' && $loop->last)
                    <div
                        x-show="speaking"
                        x-cloak
                        x-on:click="stopSpeaking()"
                        class="flex shrink-0 cursor-pointer items-center gap-2 self-start rounded-full border border-teal/30 bg-teal/10 px-3 py-1.5 text-xs font-medium text-teal animate-pulse"
                    >
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="size-3.5">
                            <path d="M4 9v6h4l5 4V5L8 9H4Z" />
                            <path d="M17 9a4 4 0 0 1 0 6" />
                            <path d="M19.5 7a7.5 7.5 0 0 1 0 10" />
                        </svg>
                        Kairo is speaking — tap to stop
                    </div>
                @endif
            @endforeach

            <template x-for="p in pending" :key="p.id">
                <div class="flex justify-end">
                    <div class="max-w-[85%] rounded-2xl bg-accent px-4 py-2.5 text-accent-foreground sm:max-w-[75%]">
                        <p class="text-sm whitespace-pre-wrap" x-text="p.text"></p>
                    </div>
                </div>
            </template>

            @if ($pendingApproval && $pendingApproval['tool'] === 'DraftPlanTool')
                <div wire:key="approval-{{ $pendingApproval['callId'] }}" class="flex justify-start">
                    @include('pages.chat.plan-approval-card', ['approval' => $pendingApproval])
                </div>
            @elseif ($pendingApproval && $pendingApproval['tool'] === 'RenegotiateCommitmentTool')
                <div wire:key="approval-{{ $pendingApproval['callId'] }}" class="flex justify-start">
                    @include('pages.chat.renegotiation-approval-card', ['approval' => $pendingApproval, 'goal' => $renegotiationGoal])
                </div>
            @endif

            <div x-show="pending.length > 0" x-cloak class="flex justify-start">
                <div class="flex items-center gap-1.5 rounded-2xl border border-border bg-surface px-4 py-3">
                    <span class="size-1.5 animate-bounce rounded-full bg-ink-muted [animation-delay:-0.3s]"></span>
                    <span class="size-1.5 animate-bounce rounded-full bg-ink-muted [animation-delay:-0.15s]"></span>
                    <span class="size-1.5 animate-bounce rounded-full bg-ink-muted"></span>
                </div>
            </div>
        </div>

        @if ($error)
            <p class="shrink-0 text-sm text-red-400">{{ $error }}</p>
        @endif

        <div x-show="listening" x-cloak class="flex shrink-0 items-center gap-1.5 self-start text-xs font-medium text-teal">
            <span class="relative flex size-2">
                <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-teal opacity-75"></span>
                <span class="relative inline-flex size-2 rounded-full bg-teal"></span>
            </span>
            Listening…
        </div>

        {{-- Input, pinned at the bottom of the chat column --}}
        <form x-on:submit.prevent="submit()" class="mb-16 flex shrink-0 items-end gap-2 md:mb-0">
            <textarea
                x-ref="input"
                rows="1"
                placeholder="Message Kairo..."
                autocomplete="off"
                x-on:input="autoResizeInput()"
                x-on:keydown.enter="if (! $event.shiftKey) { $event.preventDefault(); submit(); }"
                class="no-scrollbar max-h-40 flex-1 resize-none rounded-3xl border border-border bg-surface px-4 py-3 text-ink shadow-lg placeholder:text-ink-muted focus:border-teal focus:ring-2 focus:ring-teal/30 focus:outline-none"
            ></textarea>
            <button
                type="button"
                x-show="voiceSupported && ! listening"
                x-cloak
                x-on:click="toggleListening()"
                class="flex size-12 shrink-0 cursor-pointer items-center justify-center rounded-full border border-border text-ink-muted shadow-lg transition hover:border-teal hover:text-teal"
                aria-label="Speak your message"
            >
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="size-5">
                    <rect x="9" y="3" width="6" height="11" rx="3" />
                    <path d="M5 11a7 7 0 0 0 14 0" />
                    <path d="M12 18v3" />
                </svg>
            </button>
            <button
                type="submit"
                x-show="! listening"
                :disabled="pending.length > 0"
                class="flex size-12 shrink-0 cursor-pointer items-center justify-center rounded-full bg-accent text-accent-foreground shadow-lg shadow-teal/20 transition hover:brightness-110 disabled:cursor-not-allowed disabled:opacity-50"
                aria-label="Send message"
            >
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-5">
                    <path d="M5 12h14M13 6l6 6-6 6" />
                </svg>
            </button>

            {{-- While recording, swap in explicit cancel/send controls — the mic keeps listening through pauses until one of these is tapped. --}}
            <button
                type="button"
                x-show="listening"
                x-cloak
                x-on:click="cancelListening()"
                class="flex size-12 shrink-0 cursor-pointer items-center justify-center rounded-full border border-red-400/40 text-red-400 shadow-lg transition hover:bg-red-500/10"
                aria-label="Cancel recording"
            >
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-5">
                    <path d="M6 6l12 12M18 6L6 18" />
                </svg>
            </button>
            <button
                type="button"
                x-show="listening"
                x-cloak
                x-on:click="sendListening()"
                class="flex size-12 shrink-0 cursor-pointer items-center justify-center rounded-full bg-accent text-accent-foreground shadow-lg shadow-teal/20 transition hover:brightness-110 animate-pulse"
                aria-label="Send recorded message"
            >
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-5">
                    <path d="M5 12h14M13 6l6 6-6 6" />
                </svg>
            </button>
        </form>
    </div>
</div>
