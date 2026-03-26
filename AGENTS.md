# Agent Instructions

## Debugging: Code-First Analysis

When a user reports a bug or unexpected behavior:

1. **Read the relevant code first.** Trace the feature's logic end-to-end in the source before doing anything else. Summarize what the code does and identify the most likely cause from the logic alone.
2. **Present your analysis and a plan.** Explain what you found in the code, state your hypothesis, and propose next steps.
3. **Only then investigate externally** (querying databases, testing APIs, checking logs, launching browsers) — and only if the code analysis is inconclusive or the user asks for it.

Avoid jumping straight into empirical investigation (SSH, curl, DB queries, browser testing) when the answer is likely in the code. Reasoning about the logic is faster and cheaper than proving it from the outside.
