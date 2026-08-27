<?php

use Tests\Support\TestEnvironmentGuard;

require_once __DIR__ . '/Support/TestEnvironmentGuard.php';

TestEnvironmentGuard::preparePhpUnitProcess(dirname(__DIR__));

require dirname(__DIR__) . '/bootstrap/autoload.php';
