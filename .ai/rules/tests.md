---
paths:
  - 'tests/**/*.php'
---

# Tests

## Testing laravel/ai tool-call approval resume/execution
Two non-obvious laravel/ai testing traps around Approvable tools:

1. `AgentResponse::fakeWithPendingApprovals([...])` does NOT round-trip tool-call arguments into the persisted `tool_calls` column (the fake step response passes `[]` for tool calls, only `pendingApprovals` is set). If you need the paused turn's arguments to actually persist (e.g. to test UI that reads them back from the conversation store), fake a raw `Laravel\Ai\Responses\Data\ToolCall` instead — it runs through the real approval-detection path in TextGenerationLoop and stores real arguments.

2. When an agent is faked via `Agent::fake()`, resuming with `Decisions::from([...])` never executes the real tool. `Laravel\Ai\Providers\Concerns\ResumesToolApprovals::resumesAgainstRealGateway()` deliberately skips tool execution when `Ai::hasFakeGatewayFor()` is true — decisions get recorded (assertable via `Agent::assertPrompted()` + `$prompt->approvalDecisions`) but `Tool::handle()` never runs and no DB side effects occur. To test approve/edit DB side effects, call the Tool's `handle()` directly with a `Request`; use `assertPrompted()` only to verify the Decision (approve/edit/reject + merged arguments) that your code built.
