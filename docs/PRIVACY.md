# Privacy and external processing

Course AI Assistant integrates with an administrator-selected external AI provider. The Moodle administrator remains responsible for determining whether that processing is lawful and appropriate for the site's users and course content.

## Data sent to the external provider

Depending on the question, a request can contain:

- the participant's bounded plain-text question;
- bounded course guidance written by a course manager;
- user-specific activity dates or completion facts;
- bounded excerpts from sources the current user can access;
- opaque source identifiers and a random support request identifier.

The plugin does not intentionally send a Moodle user ID, email address, role name, grade, submission, attempt, quiz question, quiz answer, feedback, teacher-only file or API key in the prompt.

Course excerpts can still contain personal data written into otherwise permitted course content. Administrators and course managers must therefore review content practices and the provider contract before activation.

## Moodle-side storage

- Course configuration, source metadata and lexical chunks are stored for indexing.
- Per-user/course rate windows are stored temporarily for abuse control.
- Optional usage reports contain course/day aggregates only.
- Conversation text is not stored unless retention, course permission and participant opt-in are all enabled.
- Expired conversations and rate windows are removed by scheduled purging.

The Privacy API declares plugin metadata and implements user discovery, export and deletion operations.

## Administrator checklist

Before enabling the plugin, document and approve:

- the provider and model;
- processing purpose and lawful basis;
- data-processing agreement and subprocessors;
- storage, training and retention behaviour;
- permitted regions and international transfers;
- the participant notice and acceptable-use rules;
- incident response and data-subject request procedures;
- operational rate and cost limits.

Safe defaults remain active until the administrator and course manager explicitly enable each stage.
