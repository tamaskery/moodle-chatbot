# Security policy

## Supported versions

Security fixes are provided for the latest published Course AI Assistant release. The current `0.2.0-beta1` release is a review candidate and should be used only in controlled deployments until its release gates pass.

## Reporting a vulnerability

Do not disclose a suspected vulnerability in a public GitHub issue, forum, screenshot or course.

Email **Tamas Kery at tom@tomkery.eu** with:

- the affected plugin and Moodle versions;
- the security impact and required permissions;
- reproducible steps or a minimal proof of concept;
- whether hidden, restricted, cross-course or personal data may be exposed;
- any suggested mitigation;
- a safe way to contact you.

Do not include real student data, production API keys, raw production prompts, provider responses or unnecessary course content. Use synthetic test data wherever possible.

The maintainer will acknowledge a complete report as soon as practical, coordinate validation and remediation, and agree on disclosure timing with the reporter. A release and advisory will be prepared when the issue is confirmed and materially affects users.

## Security boundaries

The plugin treats participant input, indexed course text and provider output as untrusted. Its intended invariants include:

- no cross-course retrieval;
- no access based on an index snapshot;
- current-user Search API and module-visibility revalidation before transmission and citation display;
- no quiz questions, answers, attempts, feedback, submissions or grades in the index;
- no bearer-key forwarding through redirects;
- no raw provider content or credentials in logs;
- no standing diagnostic log: incident capture requires site enablement, a one-hour manager window and per-turn participant consent;
- diagnostic records exclude credentials, headers and raw provider transport responses and expire within seven days;
- owner-scoped, expiring conversation history;
- session-key and capability checks on state-changing requests;
- administrator connection tests use saved settings, a fixed synthetic request, site-configuration capability, session-key protection and sanitized results only.

Changes that weaken these invariants must not be merged.
