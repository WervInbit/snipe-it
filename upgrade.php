<?php

if (PHP_SAPI !== 'cli' || isset($_SERVER['HTTP_USER_AGENT'])) {
    http_response_code(403);
    exit('Access denied.');
}

fwrite(
    STDERR,
    "Unsupported upgrader: this fork does not pull or migrate releases from a working checkout.\n"
    ."Follow docs/production-deployment.md with a reviewed immutable image and backup/rollback plan.\n"
);

exit(1);
