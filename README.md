
Bogart: Sinatra for PHP
=======================

This is Bogart, Sinatra for PHP. We can't be as consise as Ruby, but can at least try.

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

Requires
--------

- PHP 5.3
- MongoDB

Decisions So Far
----------------

- Sinatra is awesome. Copy it.
- MongoDB required, no ORM, no models. # use jwage's ODM?
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
- Don't impose a user auth model. Use Twitter or Facebook for that. # too late!
- PHP is better when public folder doesn't include libs but shared servers don't play nice. Pick one. # shared server unfriendly so far, apparently
- Would like i18n. Need to find a way to support/integrate that.

Change Log
----------

Version 0.1-ALPHA
- This will be the first release. Meant to be somewhat stable and usable in non-critical projects.

TODO
----

- Get Bogart working with no DB access
- Switch all routes to event handlers to allow things like filters, error handling, etc. # really?
- Make funcs for Error/NotFound.
- halt
- helpers
- Tests with PHPUnit
- execute another route from within an action.
- message queue system # maybe ignore this
- email class (SwiftMailer?)
- add sass css renderer
- what to do with javascript? new string renderer or does HTML work?
- allow strings to be passed to templates instead of filenames

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

http://vastermonster.com
http://paulthrasher.com
http://twitter.com/thrashr888
