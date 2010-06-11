<?php

namespace Bogart;

require '../lib/Bogart/ClassLoader.php';
ClassLoader::register();

$app = new App('index', 'prod', true);
$app->run();
