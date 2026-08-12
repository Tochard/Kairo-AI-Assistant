@php
    $active = $conv->id === $conversationId;
@endphp

<div wire:key="conv-{{ $conv->id }}" class="flex items-center gap-1" x-data="{ editing: false, title: @js($conv->title) }">
    <template x-if="! editing">
        <a
            href="{{ route('chat', $conv) }}"
            wire:navigate
            class="flex min-h-11 flex-1 items-center truncate rounded-lg px-3 py-2 text-sm {{ $active ? 'bg-surface-muted text-ink font-medium' : 'text-ink-muted hover:bg-surface-muted hover:text-ink' }}"
            x-text="title"
        ></a>
    </template>

    <template x-if="editing">
        <input
            x-ref="renameInput"
            x-model="title"
            x-on:keydown.enter="$refs.renameInput.blur()"
            x-on:keydown.escape="title = @js($conv->title); editing = false"
            x-on:blur="editing = false; if (title.trim() && title.trim() !== @js($conv->title)) { $wire.renameConversation('{{ $conv->id }}', title.trim()); }"
            type="text"
            maxlength="100"
            class="min-h-11 flex-1 rounded-lg border border-teal bg-surface px-3 py-2 text-sm text-ink focus:outline-none"
        >
    </template>

    <button
        type="button"
        x-show="! editing"
        x-on:click.prevent.stop="editing = true; $nextTick(() => $refs.renameInput.focus())"
        class="flex size-8 shrink-0 cursor-pointer items-center justify-center rounded-full text-ink-muted transition hover:bg-surface-muted hover:text-ink"
        aria-label="Rename chat"
    >
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="size-3.5">
            <path d="M12 20h9" />
            <path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z" />
        </svg>
    </button>
</div>
