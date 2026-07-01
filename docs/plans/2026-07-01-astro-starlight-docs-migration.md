# Documentation Migration to Astro Starlight

**Date:** 2026-07-01
**Author:** Alberto Arena (AI-assisted)
**Status:** Draft — awaiting approval

## Management Summary

**What is this?**
Today the package documentation lives in a long `README.md` plus five hand-written Markdown files in `docs/`. This plan moves all of it into a proper documentation website built with [Astro Starlight](https://starlight.astro.build/) — the same stack already used in [filament-event-sourcing](https://github.com/albertoarena/filament-event-sourcing), [envaudit](https://github.com/albertoarena/envaudit) and [codemetry](https://github.com/albertoarena/codemetry). The site is published automatically to `https://albertoarena.github.io/laravel-event-sourcing-generator` via GitHub Actions.

**Why do we need it?**
- **Consistency** — one documentation experience across all of Alberto's packages.
- **Discoverability** — searchable, navigable docs with a sidebar instead of scrolling one 300-line README.
- **Maintainability** — the biggest win: today, facts like the supported Laravel versions are copied by hand into README and `docs/`, so dropping Laravel 10 means editing several places and hoping nothing is missed. This plan makes the drift-prone facts (version matrix, command options, unsupported column types) **generated from the source code**, with a CI check that fails the build if the docs fall out of sync.
- **Lean README** — the README shrinks to a landing page (badges, one-paragraph pitch, install, quick start, link to the docs site), reducing duplication.

**Where/How is it used?**
End users read the published site to learn the `make:event-sourcing-domain` command. Contributors edit `.mdx` files under `website/` and the site redeploys on merge to `main`.

**What does it enable?**
- Versioned, branded, searchable docs.
- "Edit this page" links back to GitHub.
- A single command (`composer docs:sync`) that regenerates the code-derived sections, enforced in CI.

**Investment**
- Effort: ~1–1.5 days AI-assisted (2–4 days traditional).
- Infrastructure cost: none — GitHub Pages and Actions are free for public repos.

**Timeline**

| Phase | Description | Duration |
|-------|-------------|----------|
| 1 | Scaffold Starlight site under `website/` | ~2h |
| 2 | Migrate content into `.mdx` pages | ~4h |
| 3 | Auto-sync generator + CI check | ~3h |
| 4 | Slim the README, add deploy workflow, redirects | ~2h |

**Risks & Mitigations**

| Risk | Mitigation |
|------|------------|
| Docs drift returns over time | Code-derived sections are generated + CI `docs:sync --check` fails the build on drift |
| Broken inbound links to old `docs/*.md` (Packagist, blog posts) | Keep thin stub files in `docs/` that point to the new site for one release, then remove |
| GitHub Pages base-path bugs (broken assets/links) | Mirror the proven `astro.config.mjs` from filament-event-sourcing (`site` + `base`) |

## Changelog

- **2026-07-01** — Initial draft.
- **2026-07-01** — Review round 1: generator language = PHP; extract command options via Symfony `InputDefinition` (not regex); generator honours `test.yml` matrix excludes (Laravel 11 ≠ PHP 8.5); README compatibility becomes generated too; guide consolidated to fewer pages (mirror the existing 5 docs); old `docs/*.md` deleted after one transition release; `docs/plans/` published in a separate "Project" sidebar group.

## Purpose

Replace the manually-maintained README + `docs/` Markdown with an Astro Starlight documentation website that:

1. Reduces the README to a concise landing page.
2. Reorganises the existing `docs/` content into structured, navigable pages.
3. **Generates the drift-prone content from source code** so documentation updates automatically when the code changes (e.g. dropping a Laravel version), enforced by CI.
4. Deploys to GitHub Pages via a `deploy-website.yml` workflow, matching the pattern used in the sibling repositories.

## Current State Analysis

### Documentation inventory

| Source | Lines | Content |
|--------|-------|---------|
| `README.md` | 302 | Badges, TOC, compatibility, install, quick start, "what gets generated", full options reference, usage examples linking into `docs/` |
| `docs/basic-usage.md` | 133 | Interactive walkthrough, generated tree, sample code |
| `docs/domain-and-namespace.md` | 157 | Directory structure, `--domain`, `--namespace`, reserved-word limits |
| `docs/advanced-usage.md` | 236 | Primary key, aggregates, reactors, failed events, notifications, indentation, root |
| `docs/unit-tests.md` | 155 | `--unit-test` basic + advanced examples |
| `docs/migrations.md` | 493 | Migration parsing, create/update, exclusion, unsupported column types |

### Facts that currently drift (duplicated between code and prose)

These are the values that must become **code-derived** to satisfy the "auto-update" requirement:

1. **Compatibility matrix** (PHP / Laravel / Testbench / Spatie versions)
   - Source of truth today: `composer.json` (`php` = `^8.3|^8.4|^8.5`, `spatie/laravel-event-sourcing` = `^7.9`) and `.github/workflows/test.yml` matrix (Laravel `11.*/12.*/13.*`, Testbench `9/10/11`).
   - Duplicated in: `README.md` compatibility table, `CLAUDE.md`.
2. **Command options** (12 options with descriptions)
   - Source of truth: the `$signature` property in `src/Console/Commands/MakeEventSourcingDomainCommand.php` (lines 39–52).
   - Duplicated in: `README.md` "Usage" section (hand-rewritten, already slightly diverging from the signature text).
3. **Unsupported column types & skipped methods**
   - Source of truth: `BlueprintUnsupportedInterface::UNSUPPORTED_COLUMN_TYPES` and `::SKIPPED_METHODS` in `src/Domain/Blueprint/Contracts/BlueprintUnsupportedInterface.php`.
   - Duplicated in: `docs/migrations.md` "Unsupported column types" list.

## Target Architecture

Mirror the proven layout from `filament-event-sourcing` (Starlight `^0.32`, Astro `^5`):

```
website/
├── astro.config.mjs          # site + base = /laravel-event-sourcing-generator, sidebar, editLink
├── package.json              # @astrojs/starlight ^0.32, astro ^5, sharp ^0.33
├── public/
│   └── cover.png             # OG/Twitter card image
├── src/
│   ├── content.config.ts     # docsSchema()
│   ├── content/docs/
│   │   ├── index.mdx                      # splash / hero landing
│   │   ├── getting-started/
│   │   │   ├── installation.mdx
│   │   │   └── quick-start.mdx
│   │   ├── guide/                          # mirrors the existing 5 docs, 1:1
│   │   │   ├── basic-usage.mdx
│   │   │   ├── domain-and-namespace.mdx
│   │   │   ├── advanced-usage.mdx          # primary key, aggregates, reactors, failed events, notifications, indentation, root
│   │   │   ├── unit-tests.mdx
│   │   │   └── migrations.mdx
│   │   ├── reference/
│   │   │   ├── command-options.mdx         # imports _generated/options
│   │   │   ├── compatibility.mdx           # imports _generated/compatibility
│   │   │   └── unsupported-columns.mdx     # imports _generated/unsupported
│   │   └── _generated/                     # GENERATED — do not edit by hand
│   │       ├── compatibility.md
│   │       ├── options.md
│   │       └── unsupported-columns.md
│   └── styles/custom.css
```

`astro.config.mjs` key values:
- `site: 'https://albertoarena.github.io'`
- `base: '/laravel-event-sourcing-generator'`
- `title: 'Laravel Event Sourcing Generator'`
- `social.github`, `editLink.baseUrl` → `.../edit/main/website/`
- `sidebar` grouped as Introduction / Getting Started / Guide / Reference / **Project**.

**Publishing `docs/plans/` (open decision 4 — resolved: include).** Plans stay authored in `docs/plans/` (their canonical home) and are surfaced under a **"Project" sidebar group**. To avoid the risk of raw plan files (which carry their own non-Starlight headers) breaking the content build or polluting Pagefind search with half-baked internal text, the recommended approach is a **Project → Plans** sidebar entry that **links to the `docs/plans/` folder on GitHub** rather than rendering each plan as a site page. This shares them (you already keep plans public) without mixing internal roadmap prose into the user how-to. Rendering them natively as Starlight pages (requires adding `title` frontmatter to each plan) is a possible follow-up, not part of this migration.

### Content migration mapping

| Current | New Starlight page(s) |
|---------|-----------------------|
| `README.md` intro + "what gets generated" + quick start | `index.mdx` (hero) + `getting-started/quick-start.mdx` |
| `README.md` compatibility + install | `getting-started/installation.mdx` + `reference/compatibility.mdx` (generated) |
| `README.md` options reference | `reference/command-options.mdx` (generated) |
| `docs/basic-usage.md` | `guide/basic-usage.mdx` |
| `docs/domain-and-namespace.md` | `guide/domain-and-namespace.mdx` |
| `docs/advanced-usage.md` (pk, aggregates, reactors, failed events, notifications, indentation, root) | `guide/advanced-usage.mdx` (one page, `##` section per topic) |
| `docs/unit-tests.md` | `guide/unit-tests.mdx` |
| `docs/migrations.md` | `guide/migrations.mdx` + `reference/unsupported-columns.mdx` (generated) |

In-page manual "Table of Contents" and "⬆️ Go to TOC" / "Back to README" links are dropped — Starlight renders the right-hand TOC and sidebar automatically.

## Auto-Update Mechanism (core requirement)

**Goal:** when code changes (e.g. Laravel 10 dropped, an option added, a column type supported), the docs update with no manual edit, and CI blocks any merge where the docs are stale.

**Recommended approach: a PHP generator writing Markdown partials, checked in CI.**

The project is PHP-first, so a small framework-free PHP script keeps the toolchain in one language and avoids adding Node logic to the test pipeline.

1. **Generator script** `bin/docs-sync.php` (run via a composer alias):
   - Reads `composer.json` → PHP + Spatie constraints.
   - Reads `.github/workflows/test.yml` matrix → Laravel + Testbench versions (parse with `symfony/yaml`).
     **Must honour the matrix `include`/`exclude` rules**, not just the raw axes. The matrix excludes Laravel 11 + PHP 8.5 (Laravel 11 supports PHP 8.2–8.4 only — [confirmed](https://laravel.com/docs/11.x/releases); PHP 8.5 needs Laravel 13). A naive PHP × Laravel grid would wrongly show L11/8.5 as supported — the exact drift we are eliminating. The generated compatibility table must reflect the *effective, tested* combinations, e.g. render per-Laravel-version PHP ranges rather than a flat cross-product.
   - Extracts command options by **booting the command and reading `$command->getDefinition()->getOptions()`** (name, shortcut, default, description) via a small Testbench harness — *not* by regex-parsing the `$signature` string, which is brittle for shortcuts/defaults/descriptions.
   - Reflects `BlueprintUnsupportedInterface::UNSUPPORTED_COLUMN_TYPES` and `::SKIPPED_METHODS`.
   - Writes the generated Markdown files under `website/src/content/docs/_generated/` with a banner comment: `<!-- AUTO-GENERATED by composer docs:sync — do not edit -->`.
   - **Also updates `README.md`** in place between `<!-- BEGIN:compatibility -->/<!-- END:compatibility -->` markers (and, if kept, an options marker), so the one compatibility fact the slim README retains never drifts either. This closes the gap where a hand-kept README line re-introduces the drift the site otherwise eliminates.
2. **Consumption in docs:** the hand-written reference pages import the generated partials:
   ```mdx
   import Compatibility from '../_generated/compatibility.md';
   <Compatibility />
   ```
   (Astro renders imported `.md`/`.mdx` as components, so generated tables live in exactly one place.)
3. **Composer aliases** in `composer.json`:
   - `composer docs:sync` — regenerate the partials.
   - `composer docs:check` — run the generator to a temp dir and `diff` against the committed partials; non-zero exit on drift.
4. **CI enforcement:** add a `docs-sync` job to `test.yml` (or a dedicated `docs.yml`) that runs `composer docs:check`. A stale doc fails the build, forcing the author to run `composer docs:sync` and commit. This is the guarantee that "dropping Laravel 10" can never leave the docs behind.
5. Optionally wire `docs:sync` into the existing `composer all` chain so it runs locally alongside `test/fix/check/static`.

Two output styles are used deliberately, each where it fits:
- **Imported partials** for the Starlight `.mdx` pages — these can `import` a `.md` component, so the generated table lives in exactly one file with clean `--check` diffs. This is the primary mechanism.
- **Marker injection** (`<!-- BEGIN:x -->/<!-- END:x -->`) for `README.md` — the README is a plain file outside the content collection and cannot import components, so the generator rewrites the region between markers.

**Alternative rejected:** a *Node generator inside the website build* — parse PHP/JSON from a Node script. Rejected: duplicates PHP parsing logic in JS and only runs at build time, not in the PHP test CI where code changes are actually reviewed.

## Steps

### Phase 1 — Scaffold the site (~2h)
1. Create `website/` with `package.json` pinned to `@astrojs/starlight ^0.32`, `astro ^5`, `sharp ^0.33` (match filament-event-sourcing).
2. Add `astro.config.mjs` with `site`/`base`/`title`/`sidebar`/`editLink`/`customCss` for this repo.
3. Add `src/content.config.ts` (`docsSchema()`), `src/styles/custom.css`, `public/cover.png` placeholder.
4. Verify `npm install && npm run build` produces `website/dist`.

### Phase 2 — Migrate content (~4h)
5. Write `index.mdx` (splash hero: tagline, "Get Started", "View on GitHub", "Claude skill" action, CardGrid of features).
6. Port each `docs/*.md` file into its mapped `.mdx` page(s), stripping manual TOC/back-links and converting note callouts to Starlight `:::note` / `:::caution` asides.
7. Add cross-links using Starlight route paths (no more relative `./docs/*.md`).

### Phase 3 — Auto-sync generator (~3h)
8. Implement `bin/docs-sync.php` producing the three `_generated/*.md` partials.
9. Import the partials into `reference/compatibility.mdx`, `reference/command-options.mdx`, `reference/unsupported-columns.mdx`, and into `guide/migrations.mdx` where the unsupported list appears.
10. Add `docs:sync` and `docs:check` scripts to `composer.json`; run `docs:sync` and commit the initial partials.
11. Add a `docs-sync` job (runs `composer docs:check`) to CI.

### Phase 4 — README slim-down & deploy (~2h)
12. Reduce `README.md` to: badges (add a "Read the docs" badge → site), one-paragraph pitch, a `<!-- BEGIN/END:compatibility -->` block (populated by `docs:sync`, never hand-edited), install, a minimal quick-start block, and prominent links to the docs site, CHANGELOG, CONTRIBUTING. Remove the full options reference and per-feature example sections (now on the site).
13. Replace old `docs/*.md` (except the internal `docs/ARCHITECTURE.md`/`WORKFLOW.md`/`CHECKLIST.md`/`plans/`) with thin stubs linking to the new site pages, to preserve inbound links for **one transition release**. Delete the stubs in the release immediately after (a tracked follow-up task, not left indefinitely).
14. Add a **Project → Plans** sidebar entry linking to `docs/plans/` on GitHub (see "Publishing `docs/plans/`" above).
15. Add `.github/workflows/deploy-website.yml` (copy of the filament-event-sourcing workflow: triggers on `website/**`, builds, `upload-pages-artifact` + `deploy-pages`).
16. Confirm GitHub Pages is set to "GitHub Actions" source; update the repo's About link to the docs URL.
17. Update `CLAUDE.md` to note that compatibility/options/unsupported-type facts are code-derived and documented via `website/`, and that `composer docs:sync` must be run when those change.

### Phase 5 — Verification
18. `composer all` green, `composer docs:check` green.
19. `npm run build` in `website/` green; spot-check base-path links and the "Edit this page" link.
20. Deliberately bump a version in `test.yml` locally → confirm `composer docs:check` fails until `docs:sync` is re-run (proves the guarantee). Include an assertion that the Laravel 11 / PHP 8.5 exclusion is honoured in the generated table.

## Decisions (Review Round 1)

1. **Generator language** — ✅ **PHP** (`bin/docs-sync.php`), kept in the PHP test CI where code changes are reviewed.
2. **Old `docs/*.md` fate** — ✅ **Thin redirect stubs for one transition release, then delete** (tracked follow-up).
3. **README minimalism** — ✅ **Aggressively slim**; the one compatibility fact it keeps is generated via markers, not hand-maintained.
4. **`docs/plans/` on the site** — ✅ **Include, but as a "Project" sidebar group linking to GitHub**, not rendered into the user-docs content collection (avoids IA/search pollution; no security concern — plans are already public).

_(Note: the drift-prone user docs move to `website/`; the internal `docs/ARCHITECTURE.md` / `WORKFLOW.md` / `CHECKLIST.md` from the [CLAUDE.md refactoring plan](./2026-07-01-claude-md-refactoring.md) stay out of the Starlight content collection.)_

## References

- Reference site: [filament-event-sourcing `website/`](https://github.com/albertoarena/filament-event-sourcing/tree/main/website) — Starlight config, `deploy-website.yml`, content layout copied here.
- Other Starlight sites: [envaudit](https://github.com/albertoarena/envaudit), [codemetry](https://github.com/albertoarena/codemetry).
- [Astro Starlight docs](https://starlight.astro.build/).
- Code sources of truth: `composer.json`, `.github/workflows/test.yml`, `src/Console/Commands/MakeEventSourcingDomainCommand.php:39`, `src/Domain/Blueprint/Contracts/BlueprintUnsupportedInterface.php`.
- Current docs: `README.md`, `docs/basic-usage.md`, `docs/domain-and-namespace.md`, `docs/advanced-usage.md`, `docs/unit-tests.md`, `docs/migrations.md`.

## Feedback

_(Reviewer comments below.)_
