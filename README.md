
Bogart: Sinatra for PHP
=======================

This is Bogart, Sinatra for PHP. We can't be as consise as Ruby, but can at least try.

Installation
------------

You get going quick by using the [Bogart-sandbox project](http://github.com/thrashr888/Bogart-sandbox).

    git clone git@github.com:thrashr888/Bogart-sandbox.git project_folder
    cd project_folder
    
    git submodule add git@github.com:thrashr888/Bogart.git vendor/Bogart/
    git submodule init
    git submodule update --init --recursive

*This will get easier, I'll update this readme when I get that worked out.*

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

Cli
---

Try running the demo. You can run your own with ``bogart app_name task_name [arguments]``. ``self`` references Bogart's built-in tasks.

``$ bogart self demo``

You can see more examples in /lib/Bogart/tasks.php.

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

Ideas So Far
------------

- Sinatra is awesome. Copy it.
- MongoDB required, no ORM, no models. # support doctrine ODM?
- KISS. Refactor if things get complictated. # we're getting there
- PHP 5.3 only. Don't be afraid.
- Git, GitHub, MIT License.
- Anonymous functions for actions.
- Splats (*), regex and :named routes.
- No plugins. Just extend + use that if you need to. # would be nice to architect this in
- Config in yaml.
- Include all the PHP libs we need ourselves. But rely on good existing 3rd party libs where reasonable.
- Use mod_rewrite to keep static urls in templates reasonable. # any other reason?
- Try to stay away from view helpers. Simple templates are better.
- Supply the basic template rendering methods. # not any more!
- Keep the file structure mostly flat. Not too many classes. # sinatra is 2 files!
- Minimal file-based cache, only where absolutely required. Commit to MongoDB as much as possible.
- No built in shit like blogs or comments or user or whatever. # added user. working on email.
- Don't impose a user auth model. Use Twitter or Facebook for that. # have a basic user auth
- PHP is better when public folder doesn't include libs but shared servers don't play nice. Pick one. # supporting both
- Would like i18n. Need to find a way to support/integrate that. # let the templates/app deal?

Change Log
----------

[http://github.com/thrashr888/Bogart/commits/master](http://github.com/thrashr888/Bogart/commits/master)

**Version 0.1-ALPHA**

- This will be the first release. Meant to be somewhat stable and usable in non-critical projects.

TODO
----

- Get Bogart working with no DB access
- Switch all routes to event handlers to allow things like filters, error handling, etc. # really?
- Make funcs for ``Error/NotFound``
- ``halt``
- helpers for templates
- Tests with PHPUnit # place in ``/tests`` folder
- execute another route from within an action
- message queue system # maybe ignore this
- email class (``SwiftMailer``?)
- add ``sass css`` renderer # doesn't seem practical for PHP
- what to do with javascript? new string renderer or does HTML work?
- allow strings to be passed to templates instead of filenames
- inline templates. use the ``Template`` function like a route

License
-------

MIT

Copyright (c) 2010 Paul Thrasher

Permission is hereby granted, free of charge, to any person
obtaining a copy of this software and associated documentation
files (the "Software"), to deal in the Software without
restriction, including without limitation the rights to use,
copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the
Software is furnished to do so, subject to the following
conditions:

The above copyright notice and this permission notice shall be
included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES
OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY,
WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR
OTHER DEALINGS IN THE SOFTWARE.

Author
------

Paul Thrasher

[http://github.com/thrashr888](http://github.com/thrashr888)  
[http://vastermonster.com](http://vastermonster.com)  
[http://paulthrasher.com](http://paulthrasher.com)  
[http://twitter.com/thrashr888](http://twitter.com/thrashr888)
