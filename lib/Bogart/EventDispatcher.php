<?php

namespace Bogart;

// just wraps and brings sfEventDispatcher into our namespace

include dirname(__FILE__).'/vendor/event-dispatcher/lib/sfEventDispatcher.php';

class EventDispatcher extends \sfEventDispatcher
{
}
