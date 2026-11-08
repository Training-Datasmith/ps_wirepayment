<?php

if ($argc < 2) {
    fwrite(STDERR, "Usage: guard-runner.php <guard.php>\n");
    exit(2);
}

$target = $argv[1];
if (!is_file($target)) {
    fwrite(STDERR, "Missing guard file\n");
    exit(2);
}

$sentinel = getenv('WIREPAYMENT_GUARD_SENTINEL');
if ($sentinel === false || $sentinel === '') {
    fwrite(STDERR, "WIREPAYMENT_GUARD_SENTINEL not set\n");
    exit(2);
}

file_put_contents($sentinel, 'entered');
include $target;
file_put_contents($sentinel, "fell-through");
