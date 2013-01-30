
Bogart: Sinatra for PHP
=======================

This is Bogart, Sinatra for PHP. PHP 5.3 and MongoDB only.

Installation
------------

You get going quick by using the [Bogart-sandbox project](http://github.com/thrashr888/Bogart-sandbox).

    git clone git@github.com:thrashr888/Bogart-sandbox.git project_folder
    cd project_folder
    
    git submodule add git@github.com:thrashr888/Bogart.git vendor/Bogart/
    git submodule init
    git submodule update --init --recursive

*This should get simpler, I'll update this readme when I get that worked out.*

Examples
-------

    <?php
    
    Get('/hello', function(){
      echo 'hello world';
    });
    
    Get('/hello/:name', function($request)
    {
      # matches "GET /hello/foo" and "GET /hello/bar"
      # params[name] is 'foo' or 'bar'
      echo 'Hello '.$request->params['name'].'!';
    });

You can try out the sandbox app here:  
[http://github.com/thrashr888/Bogart-sandbox](http://github.com/thrashr888/Bogart-sandbox)

Example Filesystem
------------------

Here's a sample setup that plays nice with other code on a **shared server**. ([Sample app](http://github.com/thrashr888/Bogart-sandbox-shared))

    project (at or within docroot)
      cache
      css
      js
      views
      vendor
        Bogart

If you like to keep your app out of the docroot and are not on a shared server, this is for you. Slightly safer. ([Sample app](https://github.com/thrashr888/Bogart-sandbox))

    project
      cache
      public (docroot)
        js
        css
      vendor
        Bogart
      views

Check out the [sample public folder](http://github.com/thrashr888/Bogart-sandbox/tree/master/public/). Use that and you can put the app wherever you like.

Views
-----

Like Sinatra, there are multiple ways to render views.

- Strings
- HTML (default)
- LESS CSS
- Minify
- Mustache
- PHP
- JSON
- Twig
- Smarty (upcoming)
- Text (upcoming)
- XML (upcoming)

Some examples:

    <?php
    
    // renders views/css/main.less
    View::Less('css/main');
    
    // renders views/js/main.js minified
    View::Minify('js/main.js');
    
    // just echo it or return the string!
    echo json_encode($test);
    
    // HTML has basic var replacement with {{ title }}
    View::HTML('index', compact('posts', 'title'));
    
    // renders views/post.mustache
    View::Mustache('post', compact('post', 'title'));

Cli
---

Try running the demo. You can run your own with ``bogart app_name task_name [arguments]``. ``self`` references Bogart's built-in tasks.

``$ bogart self demo``

You can see more examples in [``/lib/Bogart/tasks.php``](http://github.com/thrashr888/Bogart/blob/master/lib/Bogart/tasks.php).

    <?php
    
    // $ bogart self echo "hello world"
    Task('echo', 'Just an echo echo echo.', function($args, Cli $cli)
    {
      $cli->output($args[2]);
    });

Requires
--------

- PHP 5.3
- MongoDB

Ideas/Philosophy
----------------

- Sinatra is awesome. Copy it.
- Usable, not theoretical.
- KISS. Refactor if things get complictated. # we're getting there. could edit more.
- **Fast**! Bogart should be very speedy and stay out of the way. # is <10ms fast enough?
- **PHP 5.3 only**.
- **MongoDB required**, no ORM, no models. we can do more if we just go for it. # support doctrine ODM?
- Git, GitHub, MIT License.
- Anonymous functions for actions.
- Splats (*), regex and :named routes.
- No plugins. Just extend + use that if you need to. # would be nice to architect this in
- Config in yaml.
- Include all the PHP libs we need, but rely on good existing 3rd party libs where reasonable.
- Use ``mod_rewrite`` to keep static urls in templates reasonable. # any other reason?
- Try to stay away from view helpers. Simple templates are better.
- Supply the basic template rendering methods.
- Keep the file structure mostly flat. Not too many classes. # sinatra is 2 files! we need a few more.
- Minimal file-based cache, only where absolutely required. Commit to MongoDB as much as possible.
- No built in junk like blogs or comments or user or whatever. # added user. working on email. basics.
- Don't impose a user auth model. Use Twitter or Facebook for that. # have a very basic session/cookie user auth. needs to be extendible
- PHP is better when public folder doesn't include libs but shared servers don't play nice. Pick one. # supporting both
- Would like i18n. Need to find a way to support/integrate that. # let the templates/app deal?
