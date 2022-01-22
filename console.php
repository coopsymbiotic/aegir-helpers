#!/usr/bin/env php
<?php

require_once __DIR__ . '/vendor/autoload.php';

use Symfony\Component\Console\Application;
use Console\Command\AegirGitPull;
use Console\Command\AegirZombieDatabases;
use Console\Command\AegirSiteProperty;

$app = new Application('Aegir Helpers', 'v1.2.0');
$app->add(new AegirGitPull());
$app->add(new AegirZombieDatabases());
$app->add(new AegirSiteProperty());
$app->run();
