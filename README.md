
Bogart: Sinatra for PHP
=======================

Here's the main idea for this framework:
Be minimal. Don't be everything. Just make decisions.

Requires
--------

- PHP 5.3
- MongoDB
- mod_rewrite
- Amazon S3?

Decisions So Far
----------------

- Sinatra is awesome. Copy it.
- MongoDB only.
- PHP 5.3 only. Don't be afraid.
- Closures for actions.
- Splats and :named routes.
- Requires mod_rewrite.
- Files are only on S3 (maybe this doesn't matter).
- Asset servers are round-robin.
- Templates are mustache.php only.
- Keep the file structure flat. Not too many classes.
- Functions are cool when namespaced.
- No plugins. Just extend if you need to.
- Config in yaml.
- No built in shit like blogs or comments or user or whatever.
- Don't impose a user auth model. Use Twitter or Facebook for that.

TODO
----

- Switch all routes to event handlers to allow things like filters, error handling, etc.
- Make funcs for Error/NotFound.
- halt
- helpers
- Tests with PHPUnit
- Ability to *pass* within an action, let the next route handle it. Will need a route/controller reorg to a continuous loop. Possibly move Route::find to the controller.

Example
-------

It lives in index.php. Check it out.

License
-------

MIT

Author
------

Paul Thrasher 2010