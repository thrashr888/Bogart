<?php

namespace Bogart;

// the default tasks provided by Bogart framework

Task('cc', function(Task $task){
  $files = glob(Config::get('bogart.dir.cache').'/*');
  foreach($files as $file)
  {
    unlink($file);
    $task->log('removed '.$file);
  }
});