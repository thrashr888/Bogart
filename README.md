
Bogart: Sinatra for PHP
=======================

Here's the main idea for this framework:
Be minimal. Don't be everything. Just make decisions.

Requires
--------

- PHP 5.3
- MongoDB
- mod_rewrite

Decisions So Far
----------------

- Sinatra is awesome. Copy it.
- MongoDB only, no ORM, no models. # use jwage's ODM?
- KISS. Refactor if things get complictated.
- PHP 5.3 only. Don't be afraid.
- Git, GitHub, OSS, MIT License.
- Closures for actions.
- Splats (*), regex and :named routes.
- No plugins. Just extend + use that if you need to.
- Config in yaml.
- Include all the PHP libs we need ourselves. But rely on good existing 3rd party libs where reasonable.
- Requires mod_rewrite to keep static urls in templates reasonable. # any other reason?
- Try to stay away from helpers. Simple templates are better.
- Files are only on S3 (maybe this doesn't matter). # probably not
- Asset servers are round-robin. # how?
- Templates are mustache.php only. # not any more!
- Keep the file structure mostly flat. Not too many classes.
- Functions are cool when namespaced.
- Minimal file-based cache, only where absolutely required. Commit to MongoDB as much as possible.
- No built in shit like blogs or comments or user or whatever. # added user. working on email.
- Don't impose a user auth model. Use Twitter or Facebook for that. # too late!
- PHP is better when public folder doesn't include libs but shared servers don't play nice. Pick one. # shared server unfriendly, apparently
- Would like i18n. Need to find a way to support/integrate that.

Change Log
----------

Version 0.1-ALPHA
- This will be the first release. Meant to be somewhat stable and usable in non-critical projects.

TODO
----

- Switch all routes to event handlers to allow things like filters, error handling, etc.
- Make funcs for Error/NotFound.
- halt
- helpers
- Tests with PHPUnit
- Ability to *pass* within an action, let the next route handle it. Will need a route/controller reorg to a continuous loop. Possibly move Route::find to the controller.
- execute another route from within an action.
- message queue system
- email class (SwiftMailer?)
- add user agent, etc. to routing setup
- add sass css renderer
- what to do with javascript? new string renderer or does HTML work?

Example
-------

It lives in index.php. Check it out.

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
