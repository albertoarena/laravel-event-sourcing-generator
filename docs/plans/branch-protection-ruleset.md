# Plan: Branch Protection Ruleset for `main`

**Date:** 2026-05-14
**Status:** COMPLETED

## Changelog

- 2026-05-14: Initial draft
- 2026-05-14: Implemented — CI updated, ruleset applied, verified
- 2026-07-01: Fixed stale required checks. When Laravel 12/13 support was added (commit `b081c9a`), the matrix job names gained an `L{laravel}` segment, so the four hard-coded required contexts (`P8.3 - prefer-lowest - ubuntu-latest`, …) no longer matched any job and blocked every PR. Replaced them with a single stable aggregate gate job **"All tests passed"** (`tests-passed` in `test.yml`, `needs: test`) plus the docs gate **"Verify generated docs are in sync"**. The ruleset now requires those two checks, which survive future matrix changes.

## Goal

Strengthen protection of the `main` branch so that:

- No one can push directly to `main` (except the owner who can bypass)
- External contributors must fork and open PRs
- CI status checks must pass before merging
- Branch cannot be deleted or force-pushed

## Current State

A ruleset named **"Default"** (ID: `3042228`) already exists with these rules:

| Rule | Status |
|------|--------|
| `deletion` | Active |
| `non_fast_forward` | Active |
| `update` (block direct pushes) | Active |
| `creation` | Active |
| `required_linear_history` | Active |
| `pull_request` (require PRs) | **Missing** |
| `required_status_checks` | **Missing** |

**Bypass actors:** Repository Admin (role 5) and Write role (role 2) can always bypass.

The existing CI workflow (`.github/workflows/test.yml`) runs on every push and PR, testing PHP 8.3 and 8.4 with both `prefer-lowest` and `prefer-stable` dependency versions. It produces these job names:

- `P8.3 - prefer-lowest - ubuntu-latest`
- `P8.3 - prefer-stable - ubuntu-latest`
- `P8.4 - prefer-lowest - ubuntu-latest`
- `P8.4 - prefer-stable - ubuntu-latest`

## Steps

### Step 1: Update CI workflow

Update `.github/workflows/test.yml` to use `actions/checkout@v4` (currently using deprecated `v2`).

### Step 2: Update existing ruleset via GitHub API

Update the "Default" ruleset (ID: `3042228`) to add:

1. **Pull request requirement** — require a PR for merging with 1 required approval, dismiss stale reviews on push
2. **Required status checks** — all 4 CI matrix jobs must pass; branch must be up to date with `main`

**Command:**

```bash
gh api repos/albertoarena/laravel-event-sourcing-generator/rulesets/3042228 \
  --method PUT \
  --input - <<'EOF'
{
  "name": "Default",
  "target": "branch",
  "enforcement": "active",
  "conditions": {
    "ref_name": {
      "include": ["~DEFAULT_BRANCH"],
      "exclude": []
    }
  },
  "bypass_actors": [
    {
      "actor_id": 5,
      "actor_type": "RepositoryRole",
      "bypass_mode": "always"
    }
  ],
  "rules": [
    {
      "type": "deletion"
    },
    {
      "type": "non_fast_forward"
    },
    {
      "type": "update"
    },
    {
      "type": "creation"
    },
    {
      "type": "required_linear_history"
    },
    {
      "type": "pull_request",
      "parameters": {
        "required_approving_review_count": 1,
        "dismiss_stale_reviews_on_push": true,
        "require_code_owner_review": false,
        "require_last_push_approval": false,
        "required_review_thread_resolution": false
      }
    },
    {
      "type": "required_status_checks",
      "parameters": {
        "strict_required_status_checks_policy": true,
        "required_status_checks": [
          { "context": "P8.3 - prefer-lowest - ubuntu-latest" },
          { "context": "P8.3 - prefer-stable - ubuntu-latest" },
          { "context": "P8.4 - prefer-lowest - ubuntu-latest" },
          { "context": "P8.4 - prefer-stable - ubuntu-latest" }
        ]
      }
    }
  ]
}
EOF
```

**What each new section does:**

| Field | Purpose |
|-------|---------|
| `pull_request` rule | Requires a PR for merging; **1 required approval**; dismisses stale reviews when new commits are pushed |
| `required_status_checks` rule | All 4 CI matrix jobs must pass; `strict_required_status_checks_policy: true` means the branch must be up to date with `main` before merging |

**Changes to bypass actors:**

- Removed Write role (actor_id 2) from bypass — only Admin (actor_id 5) can bypass, which is the repo owner

### Step 3: Verify

- Confirm ruleset is updated and visible in Settings > Rulesets
- Confirm CI workflow runs successfully after the checkout version update
- Test that direct pushes to `main` are blocked for non-admin users

## Rollback

To revert the ruleset to its previous state (without PR and status check requirements):

```bash
gh api repos/albertoarena/laravel-event-sourcing-generator/rulesets/3042228 \
  --method PUT \
  --input - <<'EOF'
{
  "name": "Default",
  "target": "branch",
  "enforcement": "active",
  "conditions": {
    "ref_name": {
      "include": ["~DEFAULT_BRANCH"],
      "exclude": []
    }
  },
  "bypass_actors": [
    {
      "actor_id": 2,
      "actor_type": "RepositoryRole",
      "bypass_mode": "always"
    },
    {
      "actor_id": 5,
      "actor_type": "RepositoryRole",
      "bypass_mode": "always"
    }
  ],
  "rules": [
    { "type": "deletion" },
    { "type": "non_fast_forward" },
    { "type": "update" },
    { "type": "creation" },
    { "type": "required_linear_history" }
  ]
}
EOF
```

## Notes

- No CODEOWNERS file is included in this plan (can be added later if desired)
- The `automatic_copilot_code_review_enabled` parameter is not used — it is not supported on free/public repos
