# Testing Course AI Guide

## Automated validation

The GitHub Actions workflow uses Moodle Plugin CI and runs against every declared Moodle branch. It performs:

- PHP lint;
- Moodle Code Checker with zero warnings;
- PHPDoc checks with zero warnings;
- plugin validation and upgrade-savepoint checks;
- Mustache and Grunt validation;
- PHPUnit tests;
- tagged Behat scenarios.

To run the same tools locally from the plugin repository:

```bash
composer create-project -n --no-dev --prefer-dist moodlehq/moodle-plugin-ci ../moodle-plugin-ci ^4
../moodle-plugin-ci/bin/moodle-plugin-ci install --plugin "$PWD" --db-host=127.0.0.1
../moodle-plugin-ci/bin/moodle-plugin-ci phplint
../moodle-plugin-ci/bin/moodle-plugin-ci phpcs --max-warnings 0
../moodle-plugin-ci/bin/moodle-plugin-ci phpdoc --max-warnings 0
../moodle-plugin-ci/bin/moodle-plugin-ci validate
../moodle-plugin-ci/bin/moodle-plugin-ci savepoints
../moodle-plugin-ci/bin/moodle-plugin-ci mustache
../moodle-plugin-ci/bin/moodle-plugin-ci grunt --max-lint-warnings 0
../moodle-plugin-ci/bin/moodle-plugin-ci phpunit --fail-on-warning
../moodle-plugin-ci/bin/moodle-plugin-ci behat --profile chrome --scss-deprecations
```

Set `MOODLE_BRANCH` and `DB` as described by Moodle Plugin CI before installation.

## Release matrix

Before changing maturity to `MATURITY_STABLE`, the release commit must pass:

| Moodle | PHP | Database | Required evidence |
| --- | --- | --- | --- |
| 4.5 | 8.1 | PostgreSQL | Full automated suite |
| 4.5 | 8.3 | MySQL | Full automated suite |
| 5.0 | 8.2 | PostgreSQL | Full automated suite |
| 5.1 | 8.2 | PostgreSQL | Full automated suite and `public/` layout |
| 5.2 | 8.3 | PostgreSQL | Full automated suite |
| 5.2 | 8.4 | MySQL | Full automated suite |

## Manual release scenarios

Use full developer debugging and synthetic data.

1. Install from the generated ZIP and confirm its top-level folder is `courseaiguide`.
2. Confirm all site settings are safe and disabled by default.
3. Add the block to a course and confirm participants cannot ask before provider and index readiness.
4. Test as administrator, editing teacher, teacher, active student, suspended student and guest.
5. Test hidden activities, hidden sections, date restrictions, groups and grouping restrictions.
6. Verify that quiz questions, answers, attempts, submissions, grades and teacher-only files are never returned.
7. Test assignment, quiz and SCORM dates plus activity and course completion.
8. Test provider success, timeout, malformed JSON, redirect, HTTP 401, 429 and 5xx responses.
9. Test no-store history, opt-in history, expiry, participant deletion and Privacy API export/deletion.
10. Test course backup/restore, block deletion, course deletion, upgrade and uninstall.
11. Test keyboard-only operation, screen-reader labels, focus, zoom and supported core themes.

Run the live provider prompt-injection protocol in [ADVERSARIAL_TESTING.md](ADVERSARIAL_TESTING.md) for every proposed provider/model combination and before each release. This is intentionally an opt-in manual test because it makes a billable external request and must never run with production course data.

Never use production course content or real participant data in public CI.
