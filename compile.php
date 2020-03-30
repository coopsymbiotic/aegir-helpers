<?php

require 'vendor/autoload.php';

use Secondtruth\Compiler as Compiler;

$compiler = new Compiler\Compiler('.');

$compiler->addIndexFile('console.php');
$compiler->addDirectory('src');
$compiler->addDirectory('vendor');

$compiler->compile("build/aegir-helpers.phar");
