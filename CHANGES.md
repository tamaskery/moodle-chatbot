# Change log

## Versioning policy

- A Git push is a development change, not automatically a published release.
- Moodle's numeric `$plugin->version` is an upgrade/build identifier. It is increased when Moodle must detect new deployable code or a database change.
- `$plugin->release` is the human-readable release name. It changes only when a tested ZIP is published and tagged in Git.
- Security fixes made after `0.2.0-beta1` are collected below for the planned `0.2.0-beta2` release.

## Unreleased - planned 0.2.0-beta2

- Adopt Course AI Assistant as the public-facing name while retaining the Marketplace component `block_courseaiguide` for upgrade compatibility.
- Add dedicated permission-boundary tests for hidden, group-restricted, date-restricted, stale and cross-course retrieval candidates.
- Add citation-mapping and orchestration regression coverage so inaccessible sources cannot reach provider prompts or displayed citations.
- Empirically test adversarial Page content against the configured model and document a repeatable live-provider protocol.
- Filter common prompt-injection patterns before course guidance or retrieved chunks are sent to the provider.
- Route deadline-extension, rescheduling and submission-window questions to authoritative Moodle activity dates.
- Add an atomic site-wide daily provider-call circuit breaker with automatic UTC reset, administrator notifications and a persistent settings warning.
- Add short-lived, manager-armed and participant-consented incident diagnostics separate from conversation history.
- Document the single-provider configuration boundary and make frontend CI validation independent of the Moodle source layout.

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
