<?php

namespace Bogart;

$libdir = realpath(__DIR__);

require 'Bogart/ClassLoader.php';
ClassLoader::register();

$app = new App('index', 'prod', true);
$app->run();
