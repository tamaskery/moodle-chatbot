# Contributing

Thank you for helping improve Course AI Guide.

## Before opening a change

- Use GitHub Issues for public bugs and feature requests.
- Report vulnerabilities privately according to [SECURITY.md](SECURITY.md).
- Keep changes compatible with PHP 8.1 syntax and Moodle 4.5–5.2 unless a separately approved release changes that range.
- Do not add provider SDKs, Composer runtime dependencies or direct reads from another Moodle component's tables.

## Development rules

- Follow Moodle PHP, JavaScript, CSS, Mustache and accessibility conventions.
- Use Moodle DML with named parameters and database-neutral SQL.
- Put user-visible text in `lang/en/block_courseaiguide.php`.
- Preserve course scoping, capability checks, active-enrolment checks, sesskey validation and current-user source reauthorization.
- Never log API keys, authorization headers, raw prompts, provider responses or retained conversation text.
- Add or update PHPUnit and Behat coverage for behaviour changes.
- Rebuild committed AMD output after changing `amd/src`.

## Validation

Run the checks in [docs/TESTING.md](docs/TESTING.md). At minimum, a pull request must pass PHP lint, Moodle Code Checker, PHPDoc, validation, Mustache, Grunt, PHPUnit and the plugin's Behat suite.

## Pull requests

Use a focused branch and describe:

- the problem and intended behaviour;
- security and privacy effects;
- Moodle, PHP and database combinations tested;
- automated and manual evidence;
- upgrade or rollback considerations.

By contributing, you agree that your contribution is licensed under GNU GPL v3 or later and that you have the right to submit it.
