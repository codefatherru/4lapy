#!/usr/bin/env php
<?php

use FourPaws\Console\ConsoleApp;

$DOCUMENT_ROOT = realpath(__DIR__);

require_once $DOCUMENT_ROOT . '/local/php_interface/vendor/autoload.php';

(new ConsoleApp($DOCUMENT_ROOT))->run();
