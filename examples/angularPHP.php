<?php

require_once(__DIR__ . '/../vendor/autoload.php');

/**
 * Serve this endpoint on a URL, include the URL in your AngularJS app, and then include the module 'test-module'
 */

Echobot\AngularPHP\Endpoints\PHPBuiltIn::create()
    ->addDirectory(__DIR__ . '/../tests/src/resources/exportedClasses')
    ->setModuleName('test-module')
    ->debug(true)
    ->run();
