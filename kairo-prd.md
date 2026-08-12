# Kairo — Product Requirements Document

**Tagline:** *The right plan, at the right moment.*
**Owner:** Tochard (Tochard Codes)
**Stack:** Laravel (official Livewire starter kit) + Flux UI + Tailwind 4, MySQL, Anthropic API (function calling), Web Speech API (voice), Paystack (future monetization)
**Purpose of this doc:** Build reference for coding this along with Claude Code. Scoped for a real, shippable v1 — not a bloated everything-app.

---

## 1. Vision

Kairo is a friendly, chatty AI personal assistant that turns messy verbal/text intentions ("I want to save ₦500k by December," "I want to get fit before my wedding") into realistic, negotiated plans — then tracks, follows up, and adapts them over time. It is not a todo app with a chatbot bolted on. The chat *is* the interface for planning, logging, and reflecting; the dashboard is the record of truth.

The defensible, unique mechanic is the **negotiation loop**: Kairo drafts a plan, the user pushes back or edits, Kairo redrafts — and it learns the user's patterns from that back-and-forth over time (preferred check-in cadence, tendency to over-commit, cross-domain patterns like "task stress predicts savings misses").

**Design tone:** Friendly and playful, not formal/corporate — but still clean and standard, not gimmicky. Mobile-first: the chat is the primary surface and most check-ins will happen from a phone, so every screen must work comfortably on small viewports before desktop polish.

## 2. Goals

- **Personal use goal:** Tochard uses it daily to manage goals, tasks, and any trackable metric (finance, fitness, habits, etc.) with a genuinely pleasant chat/voice experience.
- **Portfolio goal:** Demonstrates senior-level Laravel skills — clean architecture, queues/scheduling, third-party AI API integration with function calling, polished UI/UX, real product thinking (not CRUD-only).
- **Business goal (long-term, not v1):** Validate as an "accountability-as-a-service" product for freelancers/founders, monetized via Paystack subscriptions.

## 3. Non-Goals (v1)

- No multi-tenant / team features. Single-user (or single-user-per-account) only.
- No native mobile app — responsive mobile web instead (see §3.1), not a separate iOS/Android build.
- No financial account integrations (bank sync, Mono/Okra, etc.) — trackables are manually logged in v1.

## 3.1 Now In Scope (moved up from earlier draft)

- **Voice chat**: users can speak to Kairo, not just type — covered in §6.9. Text input remains fully supported alongside it (voice is additive, not a replacement).
- **Mobile-friendly**: the entire app — chat, dashboard, plan approval cards, quick-log — must be fully usable on a phone. No desktop-only interactions.

## 4. Target User / Persona

**Primary:** Tochard himself — a freelance developer who wants a single friendly place to plan goals, get nudged, and see progress, without the rigidity of Trello/Notion or the blandness of a plain habit tracker.

**Future (post-v1):** Solo operators — freelancers, small founders — who want PA-style accountability without hiring a human.

## 5. Core Concepts & Terminology

| Term | Meaning |
|---|---|
| **Goal** | A user's stated outcome with a target date (e.g. "Save ₦500k by Dec") |
| **Plan** | Kairo's drafted breakdown of a Goal into Tasks + Trackables + cadence, pending user approval |
| **Task** | A discrete, doable action, optionally linked to a Goal, with due date/recurrence |
| **Trackable** | A generic metric definition (name, unit, direction) — powers finance, fitness, habits, anything numeric or boolean |
| **Trackable Log** | A single timestamped entry against a Trackable |
| **Check-in** | A scheduled prompt (chat message) asking the user to log progress or reflect |
| **Miss Reason** | A short tagged reason captured when a task/check-in is missed, feeding pattern detection |
| **Commitment** | The locked, approved version of a Plan — Kairo treats this as a soft contract |

## 6. Feature Set (v1)

### 6.1 Auth & Onboarding
- Standard Laravel auth (Breeze or Fortify — pick Breeze for simplicity + Livewire).
- Short onboarding chat: Kairo introduces itself, asks 1-2 questions to set tone (e.g. preferred check-in time).

### 6.2 Chat Interface (core surface)
- Persistent chat thread(s) with Kairo — primary way to create goals, log trackables, and get follow-ups.
- Natural language intent parsing via LLM function-calling against a defined tool set (see §8).
- Chat can also just be... chat. Kairo should feel warm, not purely transactional (small talk, encouragement, streak call-outs).

### 6.3 Plan Negotiation Loop (the differentiator)
- User states a goal (chat or a "New Goal" button that opens chat pre-filled).
- Kairo drafts a Plan: milestones, suggested Tasks, a suggested Trackable + check-in cadence.
- Plan rendered as an editable card in chat (approve / edit fields / reject-and-redraft).
- On approval → Plan becomes a Commitment; Goal, Tasks, Trackable, and Reminders are created transactionally.
- User can reopen a Commitment later to renegotiate (adjust deadline, cadence) — Kairo responds warmly but can push back if renegotiation is frequent (configurable "accountability strictness," default gentle).

### 6.4 Tasks
- Standard CRUD, optionally linked to a Goal.
- Recurrence (daily/weekly/custom via simple cron-like string).
- Status: pending / done / missed (auto-flagged if past due + unactioned).

### 6.5 Trackables (generic tracker)
- User or Kairo-during-planning can define a Trackable: name, unit (currency, kg, hours, count, %, boolean/custom), direction (up-good / down-good).
- Logging via chat ("log 5000 spent on transport," "did 30 mins gym") or a quick-add UI form.
- Dashboard chart per Trackable (Chart.js line/bar depending on type) — simple, not overbuilt.

### 6.6 Reminders & Follow-ups
- Laravel Scheduler + Queues drive daily/periodic check-in messages pushed into the chat (and optionally email/browser notification).
- Check-in copy is friendly and varies (avoid robotic repetition — small template pool + randomization).
- Missed task/check-in triggers a short, low-friction "what happened?" prompt with quick-tap Miss Reason chips (e.g. "too busy," "forgot," "not feeling it," "unrealistic") — this is a tap, not an essay, to keep friction near zero.

### 6.7 Pattern Insights (lightweight in v1)
- Weekly digest message from Kairo summarizing: completion rate, streaks, and any obvious cross-domain correlation the system can compute simply (e.g. "3 of your 4 missed savings check-ins fell on weeks with 2+ overdue tasks").
- v1 keeps this rule-based/statistical (no ML) — just conditional logic over logged data. Room to get fancier later.

### 6.8 Dashboard
- Overview: active Goals/Commitments, today's Tasks, Trackable snapshots, current streaks.
- Clean, warm visual design (per Tochard's existing design sensibility — polished, not generic Bootstrap-y).

### 6.9 Voice Chat
- A mic button in the chat interface (mobile and desktop) lets the user speak instead of type.
- **Speech-to-text:** browser-native Web Speech API (`SpeechRecognition`) — zero backend cost, works well on Chrome/Android; fallback to text input on unsupported browsers (notably Safari/iOS has partial support, so detect and gracefully hide the mic button rather than break).
- **Text-to-speech (Kairo talking back):** browser-native `SpeechSynthesis` API for v1 — free, no extra infra. A richer/more natural voice (e.g. ElevenLabs or an OpenAI/Anthropic-adjacent TTS API) is a clearly-marked upgrade path once the app is validated, not required for v1.
- Voice messages are transcribed client-side to text before hitting the same chat pipeline as typed messages — no separate backend logic needed; voice is purely an input/output modality on top of the existing chat/tool-calling system in §8.
- UX: show a live transcript while listening, let the user confirm/edit before sending (avoids acting on a bad transcription), and give a subtle visual "Kairo is speaking" state during TTS playback.
- Mobile consideration: mic button must be large enough and reachable one-handed (bottom of screen, thumb zone).

## 7. Data Model (Core Tables)

```
users
  id, name, email, password, timezone, checkin_preference, strictness_level

goals
  id, user_id, title, description, target_date, status(active/completed/archived)

plans
  id, goal_id, draft_payload(json), status(draft/approved/rejected), approved_at

tasks
  id, user_id, goal_id (nullable), title, due_date, recurrence_rule (nullable),
  status(pending/done/missed)

trackables
  id, user_id, goal_id (nullable), name, unit, direction(up_good/down_good), target_value(nullable)

trackable_logs
  id, trackable_id, value, note (nullable), logged_at

reminders
  id, remindable_type, remindable_id (polymorphic -> task/trackable/goal),
  frequency_rule, next_run_at, channel(chat/email)

miss_reasons
  id, user_id, remindable_type, remindable_id (polymorphic), reason_tag, note (nullable), created_at

conversations
  id, user_id, title (nullable), created_at

messages
  id, conversation_id, role(user/assistant), content, tool_calls(json, nullable), created_at

commitments
  id, plan_id, locked_payload(json), start_date, end_date, renegotiation_count
```

## 8. AI Integration Design

### 8.1 Model & Approach
- Anthropic API (Claude) via server-side Laravel service class — never expose API key client-side.
- Use **tool/function calling** so the model can take structured actions instead of freeform text guessing:
  - `create_goal`, `draft_plan`, `create_task`, `log_trackable`, `create_trackable`, `get_progress_summary`, `update_task_status`
- System prompt encodes Kairo's persona: warm, encouraging, concise, occasionally playful — but not sycophantic; it should push back gently on unrealistic plans (e.g. "Saving ₦500k in 2 weeks on your current logged income isn't realistic — want to stretch the timeline or the amount?").

### 8.2 Conversation Flow
1. User message stored → sent to model with recent conversation history + relevant DB context (active goals/tasks summary) injected into system/user context.
2. Model responds with either plain text or a tool call.
3. Tool calls are executed server-side against Laravel models (with validation), result returned to model for a natural-language follow-up ("Got it, logged ₦5,000 under Transport 👍").
4. For `draft_plan`, the tool call doesn't write to DB immediately — it returns a structured draft rendered as an approvable card; only `approve_plan` (triggered by user action) commits it.

### 8.3 Guardrails
- Tool calls that write data always validate against the user's own records (no cross-user leakage — single-tenant now, but build it right).
- Rate-limit AI calls per user to control cost.
- Store full `tool_calls` payload per message for debugging/audit.

## 9. Non-Functional Requirements

- **Performance:** Dashboard loads should stay snappy even with months of trackable logs — paginate/aggregate rather than loading full history.
- **Reliability:** Reminders must survive shared-hosting quirks (Supervisor/cron workaround, as already solved in prior projects) — queue worker uptime is critical since it drives the core "follow-up" value prop.
- **Cost control:** Cache/summarize conversation context sent to the LLM rather than sending full history every time (rolling summary after N messages).
- **Design:** Distinctive, warm, playful visual identity — avoid generic admin-template look. Tailwind, custom type/color choices, following Anthropic's frontend-design principles for intentional (non-templated) UI.
- **Mobile-first:** Build and test every screen at mobile widths first, then scale up. Chat, plan-approval cards, and quick-log flows are the highest-traffic surfaces and must work one-handed on a phone.

## 10. Build Roadmap

**Phase 1 — Foundation**
- Auth, Goals/Tasks CRUD, basic dashboard, clean UI shell.

**Phase 2 — Trackables**
- Trackable + Log CRUD, charts, quick-log UI.

**Phase 3 — Chat + AI Core**
- Chat UI, Claude API integration, function calling for create_task/log_trackable/get_progress_summary.

**Phase 4 — Plan Negotiation**
- draft_plan/approve_plan flow, Commitments, renegotiation handling.

**Phase 5 — Reminders & Follow-ups**
- Scheduler + Queue-driven check-ins, Miss Reason capture, weekly digest.

**Phase 6 — Voice Chat**
- Mic input (Web Speech API `SpeechRecognition`), transcript confirmation UX, TTS playback (`SpeechSynthesis`), mobile mic placement/reachability.

**Phase 7 — Polish & Portfolio Prep**
- Mobile-width QA pass across every screen, visual polish, demo data, README, demo video script (great showcase for the negotiation loop + voice specifically).

**Phase 8 — Stretch**
- Upgraded TTS voice (ElevenLabs or similar), deeper pattern-insight upgrades.

## 11. Monetization Notes (post-v1, not built now)

- Freemium: free for personal single-goal use; paid tier unlocks unlimited goals/trackables + weekly AI-generated pattern reports.
- Paystack subscription billing (infra already familiar from prior projects).
- Target market: solo freelancers/founders wanting PA-style accountability.

## 12. Open Decisions (flag for later, don't block build)

- Exact LLM provider/model + cost ceiling per user.
- Whether Miss Reason chips are configurable per user or fixed set.
- Strictness level: single toggle vs. more granular settings.
