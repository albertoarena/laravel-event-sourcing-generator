# Contributing

First off, thank you for considering contributing to Laravel Event Sourcing Generator! Your contributions are greatly
appreciated and help make this project better for everyone.

The following guidelines are here to ensure the contribution
process runs smoothly.

## Prerequisites

Before contributing, make sure your environment matches the versions this package supports:

- PHP 8.3 or 8.4
- Composer 2.x
- Laravel 10.x or 11.x (pulled transitively via Orchestra Testbench)
- Spatie Laravel Event Sourcing 7.x

## Local setup

Clone your fork and bootstrap the Testbench workbench:

```bash
composer install
composer prepare   # package discovery
composer build     # build the Testbench workbench
```

## How Can I Contribute?

### 1. Reporting Bugs

If you find a bug, please:

- Check the [issue tracker](https://github.com/albertoarena/laravel-event-sourcing-generator/issues) to see if it has
  already been reported.
- Open a new issue if it hasn't been reported.
    - Use a clear and descriptive title.
    - Include steps to reproduce the issue, expected behavior, and actual behavior.
    - Provide any relevant logs, screenshots, or code snippets.

### 2. Suggesting Features

We welcome feature requests! To suggest a feature:

- Check the [issue tracker](https://github.com/albertoarena/laravel-event-sourcing-generator/issues) to see if the
  feature has already been
  suggested.
- Open a new issue labeled `feature request`.
    - Clearly explain the feature and why it would be beneficial.
    - Include examples or use cases if possible.

### 3. Submitting Code Changes

Want to fix a bug or implement a feature? Great! Here’s how to contribute code:

1. **Fork** the repository.
2. Create a new **branch** for your work:
   ```bash
   git checkout -b feature/your-feature-name
   ```
3. **Make your changes** and ensure your code adheres to the project’s style guidelines.
   ```bash
   # Run Laravel Pint
   composer fix
   # Run LaraStan analysis
   composer static
   ```
4. **Test your changes** to make sure everything works as expected.
   ```bash
   composer test
   ```
5. Refresh code coverage badge icon
   ```bash
   composer test-coverage
   ```
6. **Update `CHANGELOG.md`** with a bullet under an `## Unreleased` heading (create it if missing), describing the user-visible change.
7. **Commit** your changes following the conventions below.
8. **Push** your branch to your forked repository:
   ```bash
   git push origin feature/your-feature-name
   ```
9. Open a **pull request (PR)** to the main repository:
    - Provide a detailed description of your changes.
    - Reference any related issues.
    - Include the PR checklist below.
    - Wait for feedback or approval from the maintainers.

#### Commit message conventions

- Prefix the subject with a type matching the existing history: `Feature:`, `Fix:`, `Docs:`, `Chore:`, `Refactor:`, `Test:`.
- Keep the subject line under 50 characters.
- Use the body to explain *what* and *why*, not *how*.
- Do not include AI/Claude co-author attribution.
- Use a heredoc for multi-line messages:
  ```bash
  git commit -m "$(cat <<'EOF'
  Feature: short subject

  Longer explanation of what changed and why.
  EOF
  )"
  ```

#### PR checklist

Copy this into your pull request description:

```markdown
- [ ] `composer test` passes
- [ ] `composer check` passes (Pint)
- [ ] `composer static` passes (PHPStan/LaraStan)
- [ ] `CHANGELOG.md` updated under "Unreleased"
- [ ] Docs updated (`README.md`, `docs/`) if behaviour changed
- [ ] New/changed stubs covered by tests
```

### Contributing patterns

Two contribution shapes are common in this package. Each has a short recipe.

#### Adding or modifying a stub

- Stubs live under `stubs/`.
- If you introduce a new stub *context* (aggregate, reactor, notification, etc.), update `src/Domain/Stubs/stub-mapping.json` so the generator picks it up.
- Template variables use both `DummyName` and `{{ kebab-case }}` / `{{kebab-case}}` forms — match the surrounding stub.
- Add coverage under `tests/Unit/` that exercises the change via `make:event-sourcing-domain`.

#### Adding a Blueprint column type

- Map the column type to a PHP type in `src/Domain/Blueprint/HasBlueprintColumnType.php`.
- Add a Faker expression in `src/Domain/Blueprint/HasBlueprintFake.php`.
- Document support (or any caveats) in `docs/migrations.md`.
- Add a parser test under `tests/Unit/` that uses a migration with the new column type.

### Improving Documentation

If you find areas in the documentation that can be improved:

- Open an issue to discuss your proposed changes.
- Submit a pull request with your updates.

## Etiquette

To maintain a welcoming and collaborative environment:

- **Be respectful:** Treat everyone with kindness and respect, even when there are disagreements.
- **Be constructive:** Provide helpful, actionable feedback. Avoid harsh criticism.
- **Be inclusive:** Encourage diverse perspectives and ensure your contributions are accessible to everyone.
- **Be patient:** Remember that maintainers are volunteers and may not respond immediately. Allow time for reviews and
  discussions.
- **Acknowledge contributions:** Give credit to other contributors where applicable.

## Code Guidelines

- This project uses [Laravel Pint](https://laravel.com/docs/11.x/pint) and its code is analysed
  using [LaraStan](https://github.com/larastan/larastan).
- Write clear, maintainable, and well-documented code.
- Ensure your code passes all tests and adheres to the project’s formatting rules.

Useful composer scripts:

```bash
composer fix      # auto-fix code style with Pint
composer check    # verify code style without fixing (Pint --test)
composer static   # PHPStan / LaraStan analysis
composer test     # run the PHPUnit suite
composer all      # test + fix + check + static
```

The GitHub Actions workflows under `.github/workflows/` run the same checks on every pull request.

### Testing

- Tests run under [Orchestra Testbench](https://github.com/orchestral/testbench) with PHPUnit 11/12.
- Tests are organised by feature area under `tests/Unit/`.

### Security advisories (`composer audit`)

`composer audit` may flag CVEs that originate in **transitive test dependencies** (for example, the Laravel framework version pulled in by `orchestra/testbench`). These do not affect consumers of this library:

- The package itself requires only `illuminate/contracts` and `illuminate/support` with no version pin, so end users bring their own Laravel.
- Laravel framework is pulled in only as a dev dependency, used to run the test suite.

When triaging an audit warning, check whether the affected package appears under `require` in `composer.json`. If it only appears under `require-dev` (or as a transitive dev dependency), it is a test-environment concern, not a library-consumer concern. Decide whether to bump the dev dependency on its own merits, not solely to silence the audit.

Dependabot PRs that bump `require` dependencies should be merged once tests pass. Dependabot PRs that bump `require-dev` dependencies — especially major bumps of `orchestra/testbench` or Laravel — should be evaluated against the supported Laravel matrix before merging.

## Getting Help

If you need help, feel free to:

- Ask a question by opening an issue.
- [Contact the main developer](https://github.com/albertoarena/)

Thank you for contributing! Your support helps keep this project thriving.