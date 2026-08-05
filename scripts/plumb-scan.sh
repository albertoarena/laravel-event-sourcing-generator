#!/usr/bin/env bash
#
# Request an asynchronous quality re-scan of this package on plumbphp.dev.
#
# Run manually after pushing changes that affect the score (CI config,
# SECURITY.md, dependency updates, a new release). The endpoint is public and
# unauthenticated, so keep calls infrequent to be a good API citizen.
#
#   API: POST https://plumbphp.dev/api/v1/packages/{vendor}/{name}
#   Docs: https://plumbphp.dev/api/docs#/operations/v1.packages.scan
#
# Usage:
#   scripts/plumb-scan.sh                 # scans this package (default below)
#   scripts/plumb-scan.sh vendor name     # scans an arbitrary package
#
set -euo pipefail

VENDOR="${1:-albertoarena}"
NAME="${2:-laravel-event-sourcing-generator}"

BASE="https://plumbphp.dev/api/v1"
URL="${BASE}/packages/${VENDOR}/${NAME}"

echo "Requesting scan: ${VENDOR}/${NAME}"
echo "POST ${URL}"

# -s silent, -S show errors, -w capture the status code on its own line.
response="$(curl -sS -X POST \
  -H "Accept: application/json" \
  -w $'\n%{http_code}' \
  "${URL}")"

status="$(printf '%s' "${response}" | tail -n1)"
body="$(printf '%s' "${response}" | sed '$d')"

[ -n "${body}" ] && printf '%s\n' "${body}"

case "${status}" in
  200|202)
    echo "OK (HTTP ${status}) — scan queued. View: https://plumbphp.dev/${VENDOR}/${NAME}"
    ;;
  404)
    echo "Not found (HTTP 404) — package unknown to plumbphp yet." >&2
    exit 1
    ;;
  *)
    echo "Unexpected response (HTTP ${status})." >&2
    exit 1
    ;;
esac
