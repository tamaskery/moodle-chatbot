# Change log

## 0.2.0-beta1 — 2026-08-04

- Identify Tamas Kery as the plugin owner and maintainer.
- Add explicit Moodle 4.5–5.2 compatibility metadata.
- Add Marketplace listing, installation, privacy, security, support and testing documentation.
- Add multi-version GitHub Actions validation and release packaging.
- Add Moodle-compatible plugin artwork and public contribution templates.

## 0.1.3 — 2026-08-04

- Disable provider redirects so bearer credentials cannot be forwarded to another host.
- Reject oversized provider responses and malformed endpoint URLs.
- Prevent expired conversations from being revived and hide individually expired messages.
- Make conversation writes and deletion atomic.

## 0.1.2 — 2026-08-04

- Return broad assignment, quiz, SCORM, and course deadline questions as deterministic Moodle facts.
- Sort multi-activity dates chronologically and exclude hidden or currently unavailable activities.
- Keep broad date responses bounded to 20 facts and out of the AI-provider path.

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
