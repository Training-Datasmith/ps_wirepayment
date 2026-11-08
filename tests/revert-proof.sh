#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
DOCKER_BIN="${DOCKER:-docker}"
PHP_IMAGE='php@sha256:6ce95208609dc66df163ab936c970b3b34cd901b85c747102c5999f08ade9143'
WORKDIR="${1:-}"

if [ -z "${WORKDIR}" ]; then
    echo "Usage: revert-proof.sh <work-dir-containing-phpunit-phar>" >&2
    exit 2
fi

export PHP_BINARY="${PHP_BINARY:-/usr/local/bin/php}"
export WIREPAYMENT_WORK_DIR="${WORKDIR}"
export WIREPAYMENT_ROOT="${ROOT}"

if ! command -v git >/dev/null 2>&1; then
    echo "git is required for revert proofs" >&2
    exit 2
fi

HOST_PHP="$(command -v php || true)"
if [ -z "${HOST_PHP}" ]; then
    echo "php-cli required on host to orchestrate revert proofs" >&2
    exit 2
fi

export DOCKER_BIN="${DOCKER_BIN}"
export PHP_IMAGE="${PHP_IMAGE}"
export WIREPAYMENT_ROOT="${ROOT}"
exec "${HOST_PHP}" "${ROOT}/tests/revert-proof.php" "${WORKDIR}"
