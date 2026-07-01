# Pre-Commit Checklist

> Run through this before every commit. Extracted from `CLAUDE.md`.

## Before committing

- [ ] **Code style formatted** — `composer fix` (Laravel Pint) applied, `composer check` (`pint --test`) clean
- [ ] **Quality gate green** — `composer all` passes (test, fix, check, static)
- [ ] **Tracked files verified** — ran `git check-ignore <path>` on new files; nothing that should be ignored is being added
- [ ] **Scope reviewed** — `git status` / `git diff` shows only intended changes; core files (`src/`, `tests/`, `config/`, `composer.json`) touched only when the task asked for it
- [ ] **Done log updated** — `.claude/done/YYYY-MM-DD-done.md` reflects the work
- [ ] **Explicit approval** — the user asked to commit (never commit unprompted)

> The coverage badge is produced in CI (`coverage.yml` → `coverage-data` branch) — no manual regeneration or `coverage.svg` commit needed.

## Commit message format

- Type + short subject line (**max 50 characters**), e.g. `Docs: slim CLAUDE.md`
- Detailed body paragraph explaining **what** and **why** (not how)
- Use a heredoc for multi-line messages

## Hard rules

- **No Claude attribution** — never include "Generated with Claude Code" or "Co-Authored-By: Claude"
- **Never force** — no `--no-verify`, `-f`, or similar bypass flags
- **`.gitignore` may be modified** when there is a real need; still run `git check-ignore` afterwards and confirm the change to what is tracked is intended before committing it
