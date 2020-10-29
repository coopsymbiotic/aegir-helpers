#!/usr/bin/env php
<?php

require_once __DIR__ . '/vendor/autoload.php';

use Symfony\Component\Console\Application;
use Console\Command\AegirGitPull;
use Console\Command\AegirZombieDatabases;

$app = new Application('Aegir Helpers', 'v1.1.0');
$app->add(new AegirGitPull());
$app->add(new AegirZombieDatabases());
$app->run();
