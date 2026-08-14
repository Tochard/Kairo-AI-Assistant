@php
    $args = $approval['arguments'];
    $tasks = $args['tasks'] ?? [];
@endphp

<div
    x-data="{
        mode: 'view',
        submitting: false,
        callId: @js($approval['callId']),
        title: @js($args['goal_title'] ?? ''),
        description: @js($args['goal_description'] ?? ''),
        targetDate: @js($args['target_date'] ?? ''),
        cadence: @js($args['checkin_cadence'] ?? ''),
        feedback: '',
        respond(action, payload) {
            if (this.submitting) return;
            this.submitting = true;
            $wire.respondToApproval(this.callId, action, payload?.edits ?? null, payload?.feedback ?? null)
                .then(() => { this.submitting = false; })
                .catch(() => { this.submitting = false; });
        },
        approve() { this.respond('approve'); },
        saveEdit() {
            this.respond('edit', { edits: {
                goal_title: this.title.trim(),
                goal_description: this.description.trim(),
                target_date: this.targetDate.trim(),
                checkin_cadence: this.cadence.trim(),
            } });
        },
        sendReject() { this.respond('reject', { feedback: this.feedback.trim() }); },
    }"
    class="w-full max-w-[85%] rounded-2xl border border-teal/30 bg-surface p-4 shadow-lg sm:max-w-[75%]"
>
    <div class="mb-3 flex items-center gap-2 text-xs font-semibold tracking-wide text-teal uppercase">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-3.5">
            <path d="M9 11l3 3L22 4" />
            <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11" />
        </svg>
        Plan proposal
    </div>

    {{-- View mode --}}
    <template x-if="mode === 'view'">
        <div class="flex flex-col gap-3">
            <div>
                <p class="font-bold text-ink" x-text="title"></p>
                <p x-show="description" x-cloak class="mt-1 text-sm text-ink-muted" x-text="description"></p>
                <p x-show="targetDate" x-cloak class="mt-1 text-xs text-ink-muted">Target: <span x-text="targetDate"></span></p>
            </div>

            @if (! empty($tasks))
                <ul class="flex flex-col gap-1 text-sm text-ink">
                    @foreach ($tasks as $task)
                        <li class="flex items-start gap-1.5">
                            <span class="mt-1.5 size-1 shrink-0 rounded-full bg-teal"></span>
                            <span>{{ $task['title'] ?? '' }}@if (! empty($task['due_date'])) <span class="text-ink-muted">— {{ $task['due_date'] }}</span>@endif</span>
                        </li>
                    @endforeach
                </ul>
            @endif

            @if (! empty($args['trackable_name']))
                <p class="text-sm text-ink-muted">
                    Track: <span class="text-ink">{{ $args['trackable_name'] }}</span> ({{ $args['trackable_unit'] ?? '' }})
                    @if (! empty($args['trackable_target_value']))
                        — target {{ $args['trackable_target_value'] }}
                    @endif
                </p>
            @endif

            <p x-show="cadence" x-cloak class="text-sm text-ink-muted">Check-ins: <span x-text="cadence"></span></p>

            <div class="mt-1 flex flex-wrap gap-2">
                <button
                    type="button"
                    :disabled="submitting"
                    x-on:click="approve()"
                    class="min-h-11 cursor-pointer rounded-full bg-accent px-4 text-sm font-medium text-accent-foreground shadow transition hover:brightness-110 disabled:cursor-not-allowed disabled:opacity-50"
                >
                    Approve
                </button>
                <button
                    type="button"
                    :disabled="submitting"
                    x-on:click="mode = 'edit'"
                    class="min-h-11 cursor-pointer rounded-full border border-border px-4 text-sm font-medium text-ink transition hover:border-teal hover:text-teal disabled:cursor-not-allowed disabled:opacity-50"
                >
                    Edit
                </button>
                <button
                    type="button"
                    :disabled="submitting"
                    x-on:click="mode = 'reject'"
                    class="min-h-11 cursor-pointer rounded-full border border-border px-4 text-sm font-medium text-ink-muted transition hover:border-red-400 hover:text-red-400 disabled:cursor-not-allowed disabled:opacity-50"
                >
                    Reject
                </button>
            </div>
        </div>
    </template>

    {{-- Edit mode: goal title/description/target date/cadence only — task or trackable changes go through reject + redraft --}}
    <template x-if="mode === 'edit'">
        <div class="flex flex-col gap-2">
            <label class="text-xs font-medium text-ink-muted">
                Goal title
                <input type="text" x-model="title" class="mt-1 block w-full min-h-11 rounded-xl border border-border bg-surface px-3 py-2 text-sm text-ink focus:border-teal focus:ring-2 focus:ring-teal/30 focus:outline-none">
            </label>
            <label class="text-xs font-medium text-ink-muted">
                Description
                <textarea x-model="description" rows="2" class="no-scrollbar mt-1 block w-full resize-none rounded-xl border border-border bg-surface px-3 py-2 text-sm text-ink focus:border-teal focus:ring-2 focus:ring-teal/30 focus:outline-none"></textarea>
            </label>
            <label class="text-xs font-medium text-ink-muted">
                Target date
                <input type="text" x-model="targetDate" placeholder="YYYY-MM-DD" class="mt-1 block w-full min-h-11 rounded-xl border border-border bg-surface px-3 py-2 text-sm text-ink focus:border-teal focus:ring-2 focus:ring-teal/30 focus:outline-none">
            </label>
            <label class="text-xs font-medium text-ink-muted">
                Check-in cadence
                <input type="text" x-model="cadence" placeholder="e.g. weekly" class="mt-1 block w-full min-h-11 rounded-xl border border-border bg-surface px-3 py-2 text-sm text-ink focus:border-teal focus:ring-2 focus:ring-teal/30 focus:outline-none">
            </label>

            <div class="mt-1 flex flex-wrap gap-2">
                <button
                    type="button"
                    :disabled="submitting"
                    x-on:click="saveEdit()"
                    class="min-h-11 cursor-pointer rounded-full bg-accent px-4 text-sm font-medium text-accent-foreground shadow transition hover:brightness-110 disabled:cursor-not-allowed disabled:opacity-50"
                >
                    Save & approve
                </button>
                <button
                    type="button"
                    :disabled="submitting"
                    x-on:click="mode = 'view'"
                    class="min-h-11 cursor-pointer rounded-full border border-border px-4 text-sm font-medium text-ink transition hover:border-teal hover:text-teal disabled:cursor-not-allowed disabled:opacity-50"
                >
                    Cancel
                </button>
            </div>
        </div>
    </template>

    {{-- Reject mode --}}
    <template x-if="mode === 'reject'">
        <div class="flex flex-col gap-2">
            <label class="text-xs font-medium text-ink-muted">
                What should change?
                <textarea x-model="feedback" rows="2" placeholder="Tell Kairo what to adjust..." class="no-scrollbar mt-1 block w-full resize-none rounded-xl border border-border bg-surface px-3 py-2 text-sm text-ink focus:border-teal focus:ring-2 focus:ring-teal/30 focus:outline-none"></textarea>
            </label>

            <div class="mt-1 flex flex-wrap gap-2">
                <button
                    type="button"
                    :disabled="submitting"
                    x-on:click="sendReject()"
                    class="min-h-11 cursor-pointer rounded-full bg-red-500 px-4 text-sm font-medium text-white shadow transition hover:brightness-110 disabled:cursor-not-allowed disabled:opacity-50"
                >
                    Reject & redraft
                </button>
                <button
                    type="button"
                    :disabled="submitting"
                    x-on:click="mode = 'view'"
                    class="min-h-11 cursor-pointer rounded-full border border-border px-4 text-sm font-medium text-ink transition hover:border-teal hover:text-teal disabled:cursor-not-allowed disabled:opacity-50"
                >
                    Cancel
                </button>
            </div>
        </div>
    </template>
</div>
