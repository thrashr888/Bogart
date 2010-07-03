<?php

namespace Bogart;

// just wraps and brings sfEventDispatcher into our namespace

include __DIR__.'/vendor/event-dispatcher/lib/sfEventDispatcher.php';

class EventDispatcher extends \sfEventDispatcher
{
}
