#!/usr/bin/env php
<?php

require_once __DIR__ . '/vendor/autoload.php';

use Symfony\Component\Console\Application;
use Console\Command\AegirGitPull;

$app = new Application('Aegir Helpers', 'v1.0.0');
$app->add(new AegirGitPull());
$app->run();
