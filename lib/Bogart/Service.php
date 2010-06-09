<?php

namespace Bogart;

// just wraps the sfService container and brings it into our namespace

require 'vendor/fabpot-dependency-injection-07ff9ba/lib/sfServiceContainerAutoloader.php';
\sfServiceContainerAutoloader::register();

class Service extends \sfServiceContainer
{
  
}