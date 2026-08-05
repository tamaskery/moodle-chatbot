# Moodle Marketplace submission material

This file contains prepared listing text and the release gates for `block_courseaiguide`. Update version-specific evidence before each submission.

## Short description

An access-aware course AI assistant that answers from permitted Moodle content while keeping Moodle dates and completion facts authoritative.

## Full description

Course AI Assistant is a course-only block for Moodle 4.5–5.2. It helps enrolled participants find deadlines, completion requirements, next activities and explanations grounded in course content.

Deadline and completion answers are generated deterministically from current-user Moodle APIs. Textual explanations use bounded lexical retrieval over administrator- and course-approved Moodle Search areas. Every retrieved source is course-scoped and rechecked against the current user's Search API access, module visibility, availability, section visibility and grouping restrictions before it can be sent to the configured external AI provider or displayed as a citation.

The plugin excludes quiz questions and answers, attempts, feedback, submissions, grades, arbitrary files, teacher-only files and remote URL target contents. Participant access is disabled until a provider is configured, the course is enabled and a complete index succeeds.

An administrator must supply an HTTPS OpenAI-compatible Chat Completions endpoint, model and API key. This release supports one site-wide provider configuration; it does not support multiple saved providers, per-course provider selection or automatic provider fallback. Switching providers requires the administrator to replace the configured endpoint, model and API key. Provider use may incur separate charges. The administrator is responsible for the provider contract, privacy terms, data residency, participant notice and acceptable-use policy.

Optional incident diagnostics are disabled by default and separate from conversation history. Site enablement, a one-hour course-manager window and explicit consent for each participant turn are all required. Records expire after at most seven days, are visible only to users with course AI assistant management permission, and exclude API keys, headers and raw provider transport responses.

Conversation history and aggregate statistics are disabled by default. History requires administrator retention, course-manager permission and participant opt-in. The plugin implements Moodle's Privacy API, backup/restore, scheduled retention purging and aggregate-only reporting.

## Listing metadata

- Name: Course AI Assistant
- Component: `block_courseaiguide`
- Plugin type: Block
- Maintainer: Tamas Kery
- Contact: tom@tomkery.eu
- Licence: GNU GPL v3 or later
- Source: <https://github.com/tamaskery/moodle-chatbot>
- Tracker: <https://github.com/tamaskery/moodle-chatbot/issues>
- Documentation: <https://github.com/tamaskery/moodle-chatbot#readme>
- Supported Moodle branches: 4.5–5.2
- External service: administrator-selected OpenAI-compatible provider
- Paid service: provider-dependent; no credits are bundled

The component and ZIP directory intentionally retain the established `courseaiguide` identifier. The public-name change must be submitted as an update to the existing Marketplace plugin, not as a new component.

## Reviewer configuration

The reviewer needs a temporary test-only API credential and compatible endpoint/model. Never place credentials in the repository, Marketplace listing, ZIP or issue tracker. Send reviewer credentials only through the private channel provided by Moodle and revoke them after review.

Recommended reviewer scenarios:

1. Install with defaults and confirm participant access is unavailable.
2. Configure the temporary provider.
3. Run **Test saved connection** and confirm only sanitized connection details are shown.
4. Add the block to a synthetic course, enable approved source types and run cron.
5. Verify deadline and completion answers.
6. Verify a source-grounded textual answer.
7. Verify hidden, group-restricted and future-restricted sources are unavailable to a student.
8. Confirm no-store and opt-in history behaviour.

## Submission assets

Prepare current screenshots without real user or course data:

- block in ready state (`docs/screenshots/course-ready-state.png`);
- chat modal with authoritative Moodle facts (`docs/screenshots/authoritative-deadlines.png`);
- source-grounded explanation with citations (`docs/screenshots/source-grounded-answer.png`);
- administrator settings with the API key obscured;
- course configuration and source whitelist.

## Release gates

Do not submit or mark stable until all boxes are complete:

- [ ] GitHub CI passes every job for the release commit.
- [ ] Moodle 4.5–5.2 functional jobs pass.
- [ ] PostgreSQL and MySQL jobs pass.
- [ ] Plugin prechecks report no errors and no unexplained warnings.
- [ ] Install-from-ZIP and upgrade tests pass with developer debugging enabled.
- [ ] Privacy API compliance tests pass.
- [ ] Accessibility and restricted-content manual scenarios pass.
- [ ] Release ZIP contains one `courseaiguide` top-level directory.
- [ ] Release commit is tagged and published on GitHub.
- [ ] Screenshots and reviewer-only temporary credentials are prepared.
- [ ] Provider terms, disclaimer and data-processing responsibilities are approved.
