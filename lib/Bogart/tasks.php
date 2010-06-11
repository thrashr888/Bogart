<?php

namespace Bogart;

// the default tasks provided by Bogart framework

// $ bogart self cc
Task('cc', function(Cli $cli)
{
  $files = glob(Config::get('bogart.dir.cache', '/tmp').'/*');
  foreach($files as $file)
  {
    unlink($file);
    $cli->output('removed '.$file);
  }
}, 'Clears the cache folder.');

// $ bogart self demo
Task('demo', function(Cli $cli)
{
  $cli->output("\nWelcome to Bogart Cli Demo\n");

  $cli->output('user input args: '.print_r($cli->args, 1));

  $cli->output('echo: '.$cli->ask('echo: '));
  
  $cli->output("Interactive mode:\n(`quit` or `q` to quit)");
  $cli->interactive("\\t $ ", function($resp, $cli, $options)
  {  
    $cli->output($resp);
    if($resp == 'e')
    {
      $cli->output(print_r($options, 1));
      $cli->output('end');
      return false;
    }
    
    return 'moo: \\t $ '; // the new prompt
  }, array('test' => 1));
  
  $cli->output("You get one more shot. Last chance, buddy!");
  $cli->interactive("$ ", function($resp)
  {
    return false;
  });
}, 'A demo of the Bogart Cli');

// $ bogart self echo "hello world"
Task('echo', function(Cli $cli)
{
  $cli->output($cli->args[2]);
}, 'Just and echo echo echo.');
