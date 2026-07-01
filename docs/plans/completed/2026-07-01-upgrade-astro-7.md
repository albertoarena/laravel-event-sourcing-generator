# Upgrade Starlight docs site to Astro 7 / Node 22

**Date:** 2026-07-01
**Author:** Alberto Arena (AI-assisted)
**Status:** ✅ Completed 2026-07-01 (PR #26). Phase 7 (rollout to the other 5 Starlight repos) is delegated to **per-repo plans** — each repo gets its own plan based on this proven runbook, so history stays discoverable at repo level. Not tracked centrally here.

## Management Summary

**What is this?**
The documentation site (built with Astro Starlight) currently runs on **Astro 5 / Starlight 0.32 / Node 20**. GitHub flagged 6 security advisories in Astro/esbuild that are **only fixed in Astro 6.4.6+**, so there is no in-place patch on the 5.x line — clearing them requires a major upgrade to **Astro 7**, which in turn requires **Node ≥ 22.12**. This plan defines a **repeatable procedure** to perform that upgrade, verified locally, and to apply the same steps to the author's other Starlight repos.

**Why do we need it?**
- Resolve the 6 open Dependabot advisories (2 high, 2 medium, 2 low).
- Stay on a supported Astro/Starlight line.
- Close a CI gap: today the required checks do **not** build the website, so a build-breaking dependency bump can pass review and only fail at deploy. This plan adds a build check so that can't happen again.

**Reusability:** the author maintains several Starlight docs sites ([filament-event-sourcing](https://github.com/albertoarena/filament-event-sourcing), [envaudit](https://github.com/albertoarena/envaudit), [codemetry](https://github.com/albertoarena/codemetry)). Once validated here, the **same runbook (Phases 1–6)** applies to each — only per-repo values differ.

**Risk:** low. It's a build-time toolchain upgrade for a static site; nothing ships to end users at runtime. Node 22 is already available locally (via nvm) and on GitHub Actions, so every step is verifiable before merge.

**Investment:** ~2–3h for this repo (mostly config-migration + verification); ~1h per additional repo once the pattern is proven.

## Changelog

- **2026-07-01** — Initial draft.
- **2026-07-01** — ✅ Implemented for this repo (PR #26). Astro 5→7, Starlight 0.32→0.41, sharp 0.33→0.35, Node 20→22. Breaking changes confirmed & fixed exactly as predicted: Starlight `social` object→array, content collection `docsLoader()`, and generated partials moved out of the collection (`src/content/docs/_generated` → `src/generated`). Added the `Website builds` CI gate (Node 22) and made it a required check. Dependabot #23/#24 closed as superseded. **Phase 7 (roll out to filament-event-sourcing / envaudit / codemetry) remains.**

## Purpose

Upgrade this repo's docs site to Astro 7 + current Starlight on Node 22, resolving the security advisories, and capture the procedure as a reusable runbook for the author's other Starlight sites.

## Findings (from investigation, not assumptions)

- **Build fails on Node 20** with Astro 7: `Node.js v20.13.0 is not supported by Astro! Please upgrade to ">=22.12.0"`.
- **Astro advisories** are first-patched in `6.1.6` / `6.1.10` / `6.3.3` / `6.4.6` — **all ≥ 6.x**; the two *high* ones need **≥ 6.4.6**. No 5.x fix exists.
- **Node 22 is available locally** via nvm (`22.22.x`, `22.23.0`) → builds can be verified before merge.
- **Dependabot PRs #23/#24** propose Astro `^7.0.4` + Starlight `^0.41.1`. They pass the current required checks because those checks don't build the site — this plan supersedes them.

## Known breaking changes to handle

Confirmed / likely, to be finalised against the official upgrade guides and the actual build output (no guessing — the build surfaces the rest):

1. **Node ≥ 22.12** — bump `actions/setup-node` in `deploy-website.yml` (and any website CI) from `20` to `22`; add an `.nvmrc` pinning `22`.
2. **Starlight `social` config** — changed from an object (`social: { github: '…' }`) to an **array** of `{ icon, label, href }`. `astro.config.mjs` must be migrated.
3. **Content collection loader** — newer Starlight expects `loader: docsLoader()` (from `@astrojs/starlight/loaders`) in `src/content.config.ts`; the current config relies on legacy behaviour.
4. **Anything else** the build/`@astrojs/upgrade` flags — addressed iteratively, not pre-guessed.

## Strategy — reusable runbook

### Phase 0 — Prerequisites
1. Ensure Node 22 is active for the work: `nvm use 22` (or `.nvmrc` + `nvm use`).
2. Skim the official **Astro 5→6 and 6→7 upgrade guides** and the **Starlight 0.32→0.41 changelog**; note breaking changes touching config, content collections and components.

### Phase 1 — Branch & toolchain
3. Feature branch, e.g. `chore/upgrade-astro-7`.
4. Add `website/.nvmrc` containing `22`.
5. Bump `actions/setup-node` `node-version` `20 → 22` in `deploy-website.yml` (and the new build check below).

### Phase 2 — Dependencies
6. Prefer the official tool: `cd website && npx @astrojs/upgrade` (bumps astro + integrations to compatible versions). Fallback: manually set `astro`, `@astrojs/starlight`, `sharp` to current majors and `npm install`.
7. Commit the refreshed `package.json` + `package-lock.json`.

### Phase 3 — Config migration
8. `astro.config.mjs` — migrate `social` to the array form; apply any other config changes from the guides.
9. `src/content.config.ts` — adopt `loader: docsLoader()` if required by the new Starlight.
10. Fix any other deprecations surfaced by the build.

### Phase 4 — Build & verify (Node 22)
11. `nvm use 22 && npm run build` — iterate to green.
12. `npm run preview` (or `dev`) and spot-check: home/hero, sidebar groups, generated partials (compatibility/options/unsupported), asides, base-path links, and the light/dark rendering (`custom.css` still applies).
13. Regenerate covers if the `sharp` bump changed output: `npm run cover` (visually diff).

### Phase 5 — CI guardrail (prevents recurrence)
14. Add a **website-build job** (Node 22) — either a new `.github/workflows/website.yml` triggered on `website/**`, or a job in an existing workflow — that runs `npm ci && npm run build`.
15. Add its check (e.g. **"Website builds"**) to the `main` branch-protection ruleset's required checks, alongside `All tests passed` and `Verify generated docs are in sync`. This is why the broken Dependabot bump would now be caught pre-merge.

### Phase 6 — Land & clean up
16. Open PR; confirm all required checks green (incl. the new build check).
17. Merge (admin bypass for the review rule); confirm the Pages deploy succeeds on Node 22 and the live site is intact.
18. Close Dependabot **#23/#24** (superseded).
19. Optional: add `.github/dependabot.yml` to group npm bumps under `/website` so future majors arrive as one reviewed PR rather than several.

### Phase 7 — Roll out to the other repos
20. Apply Phases 1–6 to `filament-event-sourcing`, `envaudit`, `codemetry`. Per-repo differences are limited to the values in the table below; the steps are identical.

## Per-repo application table

| Item | This repo | Notes for other repos |
|------|-----------|-----------------------|
| Site config | `website/astro.config.mjs` (base `/laravel-event-sourcing-generator`) | different `base`, `title`, `social` href |
| Node bump | `deploy-website.yml` `20 → 22`, add `.nvmrc` | same workflow name/pattern |
| Config migration | `social` array, `content.config.ts` loader | same edits |
| Build check | new required check "Website builds" | replicate; add to each repo's ruleset |

## Risks & Mitigations

| Risk | Mitigation |
|------|------------|
| Local env can't build Astro 7 | Node 22 present via nvm — `nvm use 22` before building |
| Starlight 0.41 layout/CSS changes affect `custom.css` | Phase 4 visual spot-check across pages and both themes |
| Unforeseen config breaking changes | Use `@astrojs/upgrade` + iterate on real build errors; don't pre-guess |
| Ecosystem divergence while mid-rollout | Phase 7 brings all repos to parity; low risk since sites are independent |
| Adding a required check is governance | Explicitly called out in Phase 5; applied via the same ruleset API used for the gate job |

## References

- Astro upgrade guides (5→6, 6→7) and `@astrojs/upgrade` CLI.
- Starlight changelog 0.32 → 0.41 (config: `social`, content loader).
- Dependabot PRs #23, #24; advisories on the repo's Security tab.
- Related: `docs/plans/branch-protection-ruleset.md` (required-checks mechanism), `docs/plans/2026-07-01-astro-starlight-docs-migration.md` (completed).

## Feedback

_(Reviewer comments below.)_
