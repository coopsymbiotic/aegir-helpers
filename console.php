#!/usr/bin/env php
<?php

require_once __DIR__ . '/vendor/autoload.php';

use Symfony\Component\Console\Application;

$app = new Application('Aegir Helpers', 'v1.3.1');
$app->add(new \Console\Command\AegirGitPull());
$app->add(new \Console\Command\AegirZombieDatabases());
$app->add(new \Console\Command\AegirZombieGrants());
$app->add(new \Console\Command\AegirSiteProperty());
$app->run();
