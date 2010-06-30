<?php

namespace Bogart;

// the default tasks provided by Bogart framework

// $ bogart self cc
Task('cc', 'Clears the cache folder.', function($args, Cli $cli)
{
  $files = glob(Config::get('bogart.dir.cache', '/tmp').'/*');
  foreach($files as $file)
  {
    unlink($file);
    $cli->output('removed '.$file);
  }
});

// $ bogart self demo
Task('demo', 'A demo of the Bogart Cli.', function($args, Cli $cli)
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
});

// $ bogart self echo "hello world"
Task('echo', 'Just an echo echo echo.', function($args, Cli $cli)
{
  $cli->output($args[2]);
});

// $ bogart self init project_name
Task('init', 'Make a new project.', function($args, Cli $cli)
{
  $cli->cmd('mkdir', $args[1]);
  $cli->cmd('cd', $args[1]);
  $cli->cmd('mkdir', 'public');
  $cli->cmd('mkdir', 'views');
  $cli->cmd('mkdir', 'cache');
  $cli->cmd('chmod', '777 cache');
  $cli->cmd('echo', '"<?php

namespace Bogart;

Get('/', function()
{
  
});

" index.php');
  $cli->cmd('mkdir', 'public');
});