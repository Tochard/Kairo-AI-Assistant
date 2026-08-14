@php
    $args = $approval['arguments'];
@endphp

<div
    x-data="{
        mode: 'view',
        submitting: false,
        callId: @js($approval['callId']),
        feedback: '',
        respond(action, payload) {
            if (this.submitting) return;
            this.submitting = true;
            $wire.respondToApproval(this.callId, action, payload?.edits ?? null, payload?.feedback ?? null)
                .then(() => { this.submitting = false; })
                .catch(() => { this.submitting = false; });
        },
        approve() { this.respond('approve'); },
        sendReject() { this.respond('reject', { feedback: this.feedback.trim() }); },
    }"
    class="w-full max-w-[85%] rounded-2xl border border-teal/30 bg-surface p-4 shadow-lg sm:max-w-[75%]"
>
    <div class="mb-3 flex items-center gap-2 text-xs font-semibold tracking-wide text-teal uppercase">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-3.5">
            <path d="M3 12a9 9 0 1 1 2.6 6.4" />
            <path d="M3 21v-5h5" />
        </svg>
        Renegotiate plan
    </div>

    <template x-if="mode === 'view'">
        <div class="flex flex-col gap-3">
            <p class="font-bold text-ink">{{ $goal->title ?? 'This goal' }}</p>

            <ul class="flex flex-col gap-1 text-sm text-ink-muted">
                @if (! empty($args['new_target_date']))
                    <li>New target: <span class="text-ink">{{ $args['new_target_date'] }}</span></li>
                @endif
                @if (! empty($args['new_cadence']))
                    <li>New check-ins: <span class="text-ink">{{ $args['new_cadence'] }}</span></li>
                @endif
                @if (! empty($args['reason']))
                    <li>Reason: <span class="text-ink">{{ $args['reason'] }}</span></li>
                @endif
            </ul>

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
                    x-on:click="mode = 'reject'"
                    class="min-h-11 cursor-pointer rounded-full border border-border px-4 text-sm font-medium text-ink-muted transition hover:border-red-400 hover:text-red-400 disabled:cursor-not-allowed disabled:opacity-50"
                >
                    Reject
                </button>
            </div>
        </div>
    </template>

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
                    Reject
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
