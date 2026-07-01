# CLAUDE.md Refactoring — "RAM not disk"

**Date:** 2026-07-01
**Author:** Alberto Arena (AI-assisted)
**Status:** ✅ Completed 2026-07-01 (commit `6c01098`)

## Management Summary

**What is this?**
`CLAUDE.md` has grown to ~300 lines / ~11 KB. Following the principle from the author's article *["CLAUDE.md is RAM, not disk"](https://albertoarena.it/posts/claude-md-is-ram-not-disk/)*, this plan slims `CLAUDE.md` down to a under-one-minute read and moves the detailed, occasionally-needed material into dedicated `docs/` files that Claude loads only when relevant.

**Why do we need it?**
Every line in `CLAUDE.md` is re-read on every turn of every session — it is working memory that is "paid for" constantly, whether or not the current task needs it. A long architecture writeup or plan-authoring guide "does not make Claude smarter"; it just taxes every turn. Moving that content to `docs/` keeps it available on demand without the per-turn cost.

**The rule applied:** *"Does Claude need this every time, or only sometimes? Every time goes in `CLAUDE.md`. Sometimes goes in `docs/`."*

**What changes for the reader:** `CLAUDE.md` keeps the overview, exact commands, pinned stack, universal conventions, and hard safety boundaries — plus one-line pointers ("the address, not the content") to three new files.

**Two behavioural changes folded into this review:**
1. Plans move from `.claude/plans/{developer}/` to `docs/plans/` (consolidating the split that already exists).
2. `.gitignore` may now be modified (previously forbidden).

**Effort:** ~1.5–2h AI-assisted. No infrastructure, no code changes.

## Changelog

- **2026-07-01** — Initial draft.
- **2026-07-01** — Review round 1: retire `developers.json`; push Testing/Dependencies detail to `ARCHITECTURE.md`; nested `CLAUDE.md` confirmed out of scope; recommend renaming `INSTRUCTIONS.md` → `WORKFLOW.md`.
- **2026-07-01** — Review round 2: file naming confirmed — `ARCHITECTURE.md` + `WORKFLOW.md` + `CHECKLIST.md`. All open decisions resolved; plan ready for approval.
- **2026-07-01** — ✅ Implemented (commit `6c01098`): CLAUDE.md slimmed 246→57 lines; `docs/ARCHITECTURE.md`, `docs/WORKFLOW.md`, `docs/CHECKLIST.md` created; plans consolidated into `docs/plans/` (`developers.json` retired); `.gitignore` edits permitted. Moved to `completed/`.

## Purpose

1. Reduce `CLAUDE.md` to essentials that are genuinely needed on *every* session.
2. Extract the "sometimes" content into `docs/WORKFLOW.md`, `docs/ARCHITECTURE.md`, and `docs/CHECKLIST.md`, referenced from `CLAUDE.md`.
3. Apply two governance changes: consolidate plan storage into `docs/plans/`, and permit `.gitignore` edits.

## Current State Analysis

`CLAUDE.md` today contains (top to bottom):

| Section | Needed every session? | Destination |
|---------|----------------------|-------------|
| Overview | Yes (trim to 2–3 sentences) | **CLAUDE.md** |
| Development Commands (test, quality, package) | Yes — exact syntax | **CLAUDE.md** |
| Architecture › Core Components | No — reference material | `docs/ARCHITECTURE.md` |
| Architecture › Directory Structure | No | `docs/ARCHITECTURE.md` |
| Architecture › Key Generation Flow | No | `docs/ARCHITECTURE.md` |
| Architecture › Migration Parsing Details | No | `docs/ARCHITECTURE.md` |
| Testing | Partly | one-liner in CLAUDE.md; detail → `docs/ARCHITECTURE.md` |
| Dependencies | Yes (pinned versions) | compressed stack block in **CLAUDE.md** |
| Important Notes | Yes (short universal rules) | **CLAUDE.md** (trimmed) |
| Git Commit Conventions | Only when committing | `docs/CHECKLIST.md` |
| Claude AI Documentation (plans, structure, mgmt summary, completion, doc requirements) | Only when writing plans/docs | `docs/WORKFLOW.md` |
| Task Tracking (daily done files) | Only when logging work | `docs/WORKFLOW.md` |
| Critical Boundaries (do-not-read, core files) | Yes — safety | **CLAUDE.md** (compressed) |
| Git Safety Rules | Partly — safety + commit process | split: hard rules → CLAUDE.md; commit process → `docs/CHECKLIST.md` |

**Existing plan-storage split to resolve:**
- `.claude/plans/alberto/2026-05-19-expand-contributing-guide.md` and `.claude/plans/developers.json` (old scheme)
- `docs/plans/*.md` (current scheme, already in use)

## Target Structure

```
CLAUDE.md                 # slim: overview, commands, stack, conventions, boundaries, pointers
docs/
├── ARCHITECTURE.md       # core components, directory structure, generation flow, migration parsing, testing detail
├── WORKFLOW.md           # plan authoring + format + review + completion, doc requirements, daily task tracking
├── CHECKLIST.md          # pre-commit checklist: run composer all, verify tracked files, commit format, ask-before-commit
└── plans/                # single home for all plans (+ completed/)
```

Mirrors the article's template (root `CLAUDE.md` + `docs/DESIGN.md`/`PLAN.md`/`DECISIONS.md`), adapted to this repo as `ARCHITECTURE`, `WORKFLOW`, `CHECKLIST`. `WORKFLOW` is used over the article's `INSTRUCTIONS` because "instructions" describes all of CLAUDE.md, whereas this file is specifically *how work gets done here*. A `docs/DECISIONS.md` is intentionally **not** created now — the article warns against "empty scaffolding"; add it only when a real need appears.

### Slim `CLAUDE.md` outline (target)

```
# CLAUDE.md
## Overview            — 2–3 sentences
## Development Commands — test / test-coverage / fix / check / static / all / package cmds
## Stack               — PHP 8.3–8.5, Laravel 11(dep)/12/13, Spatie ES 7.x, nikic/php-parser, aldemeery/onion
## Conventions         — 4-space indentation, aggregates uuid-only, failed events & notifications opt-in
## Critical Boundaries — DO NOT read: /.docs /.idea /.phpunit.cache /reports /vendor
                       — Treat src/ tests/ config/ composer.json as core; modify only when asked
                       — Git safety: never --no-verify/-f; run `git check-ignore` before adding; ask before committing
## More detail
  - Architecture & generation flow → docs/ARCHITECTURE.md
  - Plans, docs & task tracking     → docs/WORKFLOW.md
  - Pre-commit checklist            → docs/CHECKLIST.md
```

Target size: ~70–90 lines (readable in under a minute), down from ~300.

## Behavioural Changes (baked into the rewrite)

### 1. Plans → `docs/plans/`
- `docs/WORKFLOW.md` "Storage" states plans live in `docs/plans/` (`docs/plans/completed/` for finished ones); the `{developer}` subfolder scheme is dropped.
- Migrate `.claude/plans/alberto/2026-05-19-expand-contributing-guide.md` → `docs/plans/`.
- Decide on `.claude/plans/developers.json`: retire it (no longer referenced) or move under `docs/plans/`. **Recommendation:** retire — the per-developer split is gone.
- Remove the now-empty `.claude/plans/` directory.

### 2. Allow modifying `.gitignore`
- Remove the two "NEVER modify `.gitignore`" statements (Critical Boundaries + Git Safety Rules).
- Replace with: `.gitignore` may be modified when needed; still verify with `git check-ignore` and ask before committing changes that alter what is tracked.

## Steps

1. **Create `docs/ARCHITECTURE.md`** — move the entire "Architecture" section (Core Components, Directory Structure, Key Generation Flow, Migration Parsing Details) and the "Testing"/"Dependencies" detail verbatim; add a "Back to CLAUDE.md" note at top.
2. **Create `docs/WORKFLOW.md`** — move "Claude AI Documentation" (Storage, Plan File Format, Plan Structure, Writing Effective Plans, Plan Review, Management Summary, Plan Completion, Documentation Requirements) and "Task Tracking". Update Storage + Plan Completion to reference `docs/plans/` (drop `{developer}`).
3. **Create `docs/CHECKLIST.md`** — a concrete pre-commit checklist assembled from "Git Commit Conventions" + the process parts of "Git Safety Rules":
   - [ ] `composer all` green (test, fix, check, static)
   - [ ] `git check-ignore <path>` verified for new files
   - [ ] Commit subject ≤ 50 chars; body explains what & why
   - [ ] No Claude attribution / no `--no-verify` / no force
   - [ ] Daily done file updated (`.claude/done/YYYY-MM-DD-done.md`)
   - [ ] Explicit user approval before committing
4. **Rewrite `CLAUDE.md`** to the slim outline above, including the three pointers and the two behavioural changes.
5. **Migrate the stray plan** `.claude/plans/alberto/...` → `docs/plans/`; retire `developers.json`; remove empty dirs.
6. **Self-review** per WORKFLOW: confirm every removed line landed in exactly one destination file (no content lost, no duplication).

## Coordination with the Starlight migration plan

`docs/ARCHITECTURE.md`, `docs/WORKFLOW.md`, `docs/CHECKLIST.md`, and `docs/plans/` are **internal contributor/AI docs, not end-user docs**. When [the Starlight site](./2026-07-01-astro-starlight-docs-migration.md) is built, its content collection must include only the user-facing pages under `website/src/content/docs/` — these `docs/*.md` files stay out of the published site. No conflict, but the two plans should be sequenced so the Starlight build's include/exclude globs account for these files.

## Decisions (Review Round 1)

1. **`developers.json`** — ✅ **Retire.** No longer referenced once the `{developer}` scheme is dropped.
2. **File naming** — ✅ **Confirmed: `ARCHITECTURE.md` + `WORKFLOW.md` + `CHECKLIST.md`.** `ARCHITECTURE` and `CHECKLIST` kept as requested. `INSTRUCTIONS.md` → **`WORKFLOW.md`** because "instructions" describes all of CLAUDE.md, whereas this file is specifically *how work gets done here* (plan authoring, task tracking, doc requirements). The article's `DESIGN/PLAN/DECISIONS` are rejected: `PLAN` collides with `docs/plans/`, `DECISIONS` would be empty scaffolding.
3. **Testing/Dependencies detail** — ✅ **Full detail → `ARCHITECTURE.md`**; only the pinned stack summary + command list remain in `CLAUDE.md`.
4. **Nested `CLAUDE.md`** — ✅ **Out of scope.** Note as a future option only if the root `CLAUDE.md` regrows.

## References

- [CLAUDE.md is RAM, not disk](https://albertoarena.it/posts/claude-md-is-ram-not-disk/) — source principle.
- Current `CLAUDE.md` (repo root).
- Related plan: [Astro Starlight docs migration](./2026-07-01-astro-starlight-docs-migration.md).

## Feedback

_(Reviewer comments below.)_
