<?php

namespace Bogart;

// just wraps and brings sfEventDispatcher into our namespace

include dirname(__FILE__).'/vendor/fabpot-event-dispatcher-782a5ef/lib/sfEventDispatcher.php';

class EventDispatcher extends \sfEventDispatcher
{
}
