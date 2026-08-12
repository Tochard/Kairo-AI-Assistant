---
name: kairo-design
description: Design and copywriting conventions specific to Kairo, a friendly AI personal-assistant Laravel app. Use whenever building or editing any UI, component, or user-facing text for Kairo — chat interface, dashboard, plan-approval cards, voice chat, reminders. Keeps every Claude Code session visually and tonally consistent with prior sessions.
---

# Kairo Design Skill

Kairo is a chat-first personal assistant app (Laravel + Livewire + Flux UI + Tailwind 4) that helps the user plan goals, track progress, and get friendly follow-ups. This skill locks in the visual and voice identity so the app doesn't drift session to session.

## Product tone

Kairo talks like a warm, competent friend who happens to be great at planning — never like a corporate SaaS dashboard, never like a customer support bot. Kairo can be playful (light humor, streak call-outs, the occasional emoji in chat bubbles) but is never silly to the point of undermining trust — it's still the tool the user relies on to hold them accountable.

Kairo pushes back gently when a plan is unrealistic, rather than agreeing to everything. That honesty is part of the personality, not a UI mode.

Copy rules (apply the frontend-design skill's writing guidance, tuned further for Kairo specifically):
- First-person warmth from Kairo in chat ("Nice, logged that!" not "Entry recorded successfully.")
- Everywhere else (buttons, labels, settings) stays plain and functional — the personality lives in the chat bubbles, not in every UI label.
- Empty states are invitations, phrased in Kairo's voice: "No goals yet — tell me something you're working toward."
- Never fake urgency or guilt-trip over missed tasks. Follow-ups are curious ("what got in the way?"), not scolding.

## Visual identity (revised 2026-08-12 — supersedes the original warm-cottage palette below)

Kairo's brand mark is a glowing teal "K" (with a chat-bubble negative-space cutout) on black — `public/images/logo.png` / `logo.webp` / `favicon.ico`. The product direction is a modern AI-platform look: dark-first, glow/gradient accents, clean geometric sans — think Linear/Vercel/ChatGPT-adjacent, not a cozy journaling app. Still friendly (rounded corners, warm copy in chat), just not warm-toned visually anymore.

**Theme default: dark-first.** The app should load in dark mode for new visitors regardless of system preference (seed `localStorage['flux.appearance'] = 'dark'` before `@fluxAppearance` runs if unset), with light mode fully built and toggleable. Dark is the primary/showcase experience.

Palette (semantic tokens in `resources/css/app.css`, not literal hex sprinkled through views):
- Brand teal (from the logo): `--color-teal` general UI accent (badges, links, icons), `--color-teal-glow` lighter/brighter variant for decorative glow/gradient use only (blurred background blobs, gradient text), `--color-accent` (deeper teal, `#0D9488`-ish) for solid button fills — needs to hold contrast with white text, so it's deliberately deeper than the glow teal.
- Secondary "suiting" accent: a soft violet (`#8B5CF6`-ish) — used sparingly to pair with teal in gradients/glows and for the "completed" goal state. Not a general-purpose UI color.
- Backgrounds/surfaces/text are cool neutrals now (not warm) — near-black dark bg, clean white/near-white light bg, gray-scale ink tones. No more taupe/cream/warm-gray.
- Missed/incomplete states stay neutral (zinc/gray) — never red. This rule survives the palette change.

Typography: a single clean modern geometric/grotesk sans across headers and body (Plus Jakarta Sans) — no separate warm display serif. Headers get weight/size, not a different typeface.

Component texture: subtle glass/blur treatment (`backdrop-blur`, translucent borders) is fair game for hero/auth surfaces to reinforce the AI-product feel, but keep dense CRUD lists (goals/tasks) on solid surfaces for readability — don't glass-ify everything.

<details>
<summary>Original palette (superseded, kept for history)</summary>

Do NOT default to the generic AI-cream-and-terracotta look or a near-black-with-neon-accent look — pick a palette that feels human and optimistic, distinct from a typical admin panel or typical AI product.

Locked palette (use these hex values directly — don't reinterpret):
- Primary accent: Warm Teal `#0F9B8E` — used for send/mic buttons, active states, chat bubble accents, primary CTAs. This is Kairo's signature color.
- Secondary/celebratory: Golden Amber `#F5A623` — reserved for streaks, goal completions, wins. Keep it rare so it stays special.
- Base background, light mode: Warm off-white `#FAF7F2`
- Base background, dark mode: Deep warm charcoal `#211E1A`
- Muted/neutral (missed check-ins, incomplete states): Warm taupe-gray `#B8A99A` — never harsh red.
- Primary text, light mode: Near-black warm gray `#2A2724`

Dark mode supported (Flux UI ships this) but chat bubbles should stay warm-toned in both modes, not go cold gray.

Typography: one characterful but readable display face for headers/greetings, a clean body face for everything else (chat text, dashboard data). Avoid overly corporate/geometric sans as the sole face — pick something with a bit of warmth in its letterforms.
</details>

Chat bubble design: Kairo's messages and the user's messages should be visually distinct but both feel soft (rounded, not sharp-cornered) — this is a conversation with a friend, not a terminal.

## Mobile-first requirements (non-negotiable for every screen)

- Design and build at ~375px width first, then scale up to desktop — never the reverse.
- Chat input bar and mic button live in the thumb-reachable bottom zone, not the top.
- Plan-approval cards, quick-log forms, and dashboard summaries must be fully usable with one thumb — no hover-dependent interactions, no tiny tap targets (44px minimum).
- Test every new screen at mobile width before considering it done.

## Voice chat UX specifics

- Mic button: large, bottom-of-screen, thumb zone. Show a clear "listening" state (subtle pulse/waveform, not a spinner).
- Always show the live transcript as the user speaks, and require a lightweight confirm (or auto-send after a short pause with an easy undo) — never silently act on a voice transcription without the user seeing what was heard.
- TTS playback (Kairo speaking back) gets its own subtle visual state distinct from "listening" — don't reuse the same animation for both.
- Voice is an alternate input to the same chat pipeline, not a separate feature — visually, a voice-originated message should look identical to a typed one once sent, just perhaps with a small mic icon tag.

## Component approach

- Use Flux UI components as the base, then restyle via Tailwind tokens (colors, radius, spacing) to match Kairo's palette rather than leaving Flux defaults untouched — an unstyled Flux app will read as generic.
- Reuse one consistent card style for anything "approvable" (plan drafts, commitments) so the user learns the pattern: this shape means "review and decide."
- Charts (trackables) should stay simple — line/bar only, no decorative chart-junk, consistent with the "calm, not stressful" tracking philosophy.

## Self-check before considering a screen done

- Does this look like Kairo, or would it work equally well as a generic SaaS dashboard? If the latter, revise.
- Does every user-facing string sound like something a warm, competent friend would actually say?
- Does it work one-handed on a phone?
- Is red/alarm styling reserved only for truly urgent moments, not routine misses?
