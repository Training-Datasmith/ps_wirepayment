#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
DOCKER_BIN="${DOCKER:-docker}"
PHP_IMAGE='php@sha256:6ce95208609dc66df163ab936c970b3b34cd901b85c747102c5999f08ade9143'
PHPUNIT_VERSION='4.8.36'
WORKDIR="$(mktemp -d)"
chmod 0700 "$WORKDIR"
cleanup() {
    rm -rf "$WORKDIR"
}
trap cleanup EXIT

"${DOCKER_BIN}" pull "${PHP_IMAGE}" >/dev/null

curl -fsSL -o "${WORKDIR}/phpunit-${PHPUNIT_VERSION}.phar" "https://phar.phpunit.de/phpunit-${PHPUNIT_VERSION}.phar"
curl -fsSL -o "${WORKDIR}/phpunit-${PHPUNIT_VERSION}.phar.asc" "https://phar.phpunit.de/phpunit-${PHPUNIT_VERSION}.phar.asc"
curl -fsSL -o "${WORKDIR}/gpg.asc" "https://sebastian-bergmann.de/gpg.asc"

EXPECTED_SHA='ab8bc3017a64cf75a2112f5e089a1d5b0c4cc2116556d60c1af5e3f584e85c5e'
ACTUAL_SHA="$(sha256sum "${WORKDIR}/phpunit-${PHPUNIT_VERSION}.phar" | awk '{print $1}')"
if [ "${ACTUAL_SHA}" != "${EXPECTED_SHA}" ]; then
    echo "PHPUnit PHAR SHA-256 mismatch" >&2
    exit 1
fi

GNUPGHOME="$(mktemp -d)"
chmod 0700 "${GNUPGHOME}"
export GNUPGHOME
gpg --batch --import "${WORKDIR}/gpg.asc" >/dev/null
GPG_OUT="$(gpg --batch --status-fd 1 --verify "${WORKDIR}/phpunit-${PHPUNIT_VERSION}.phar.asc" "${WORKDIR}/phpunit-${PHPUNIT_VERSION}.phar" 2>&1)"
echo "${GPG_OUT}" | grep -q 'VALIDSIG D8406D0D82947747293778314AA394086372C20A'
rm -rf "${GNUPGHOME}"
unset GNUPGHOME

"${DOCKER_BIN}" run --rm --network none \
    -v "${ROOT}:/src:ro" \
    -v "${WORKDIR}:/work:rw" \
    -w /src \
    -e WIREPAYMENT_WORK_DIR=/work \
    "${PHP_IMAGE}" \
    /usr/local/bin/php -d error_reporting=-1 tests/write-phpunit-xml.php

run_suite() {
    local config="$1"
    local junit="$2"
    "${DOCKER_BIN}" run --rm --network none \
        -v "${ROOT}:/src:ro" \
        -v "${WORKDIR}:/work:rw" \
        -e PHP_BINARY=/usr/local/bin/php \
        -w /src \
        "${PHP_IMAGE}" \
        /usr/local/bin/php -d error_reporting=-1 -d display_errors=1 -d display_startup_errors=1 \
        /work/phpunit-${PHPUNIT_VERSION}.phar \
        --configuration "/work/${config}" \
        --log-junit "/work/${junit}"
}

run_suite phpunit-ordered.xml junit-ordered.xml | tee "${WORKDIR}/ordered.txt"
run_suite phpunit-shuffled.xml junit-shuffled.xml | tee "${WORKDIR}/shuffled.txt"

grep -q 'OK (69 tests,' "${WORKDIR}/ordered.txt"
grep -q 'OK (69 tests,' "${WORKDIR}/shuffled.txt"

chmod +x "${ROOT}/tests/revert-proof.sh"
if [ "${RUN_REVERT_PROOFS:-0}" = "1" ]; then
    "${ROOT}/tests/revert-proof.sh" "${WORKDIR}"
fi

echo "Suite completed successfully."
