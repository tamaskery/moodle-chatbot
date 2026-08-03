# Course AI Guide block

`block_courseaiguide` is a course-only Moodle block that combines deterministic Moodle facts with access-filtered lexical retrieval over approved Moodle Search areas.

## Requirements

- Moodle 4.5, 5.0, 5.1, or 5.2
- A PHP version supported by the selected Moodle release
- An administrator-configured HTTPS OpenAI-compatible chat endpoint

The endpoint, model, and API key are unset by default. Conversation retention, aggregate statistics, and participant access are disabled by default.

## Installation

Install the directory as `blocks/courseaiguide` on Moodle 4.5/5.0, or `public/blocks/courseaiguide` in a Moodle 5.1+ source checkout, then run the standard Moodle upgrade process.

## Security model

Every ask validates the course context, capability, active enrolment or management capability, readiness, and a database-backed rate limit. Retrieved sources are course-scoped and rechecked through the owning Search area's `check_access()` plus current user modinfo before provider transmission and citation return. Indexed access snapshots never grant access.

Quiz questions, answers, attempts, feedback, submissions, grades, teacher-only files, arbitrary files, and remote URL target contents are excluded.

## Privacy

No conversation messages are stored unless the administrator permits a 1–365 day retention, the course enables history, and the participant explicitly opts in. Reports contain course/day aggregate counts only.

## Licence

GPL v3 or later.
