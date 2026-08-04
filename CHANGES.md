# Change log

## 0.1.1 — 2026-08-04

- Send GPT-5.6 Chat Completions requests with explicit `reasoning_effort: none`.
- Omit the incompatible sampling parameter for GPT-5.6 while preserving it for other providers.
- Add regression coverage for model-aware provider payloads.
- Show administrators an allowlisted failure category without exposing provider response data.

## 0.1.0 — 2026-08-03

- Initial secure MVP implementation.
- Course-only block placement and four capabilities.
- Deterministic user-specific activity dates and next-completion answers.
- Whitelisted Moodle Search-area indexing and bounded database lexical retrieval.
- Current-user source permission revalidation before provider transmission and citation return.
- Replaceable OpenAI-compatible provider using Moodle curl security.
- Accessible modal and no-JavaScript fallback.
- Optional three-gate participant-owned history, aggregate reporting, rate limiting, Privacy API, tasks, and safe backup/restore.
