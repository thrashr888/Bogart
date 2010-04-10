
Bogart: Sinatra for PHP
=======================

Here's the main idea for this framework:
Be minimal. Don't be everything. Just make decisions.

Requires:

- PHP 5.3
- MongoDB
- mod_rewrite
- Amazon S3?

Decisions so far:

- Sinatra is awesome. Copy it.
- MongoDB only.
- PHP 5.3 only. Don't be afraid.
- Closures for actions.
- Splats and :named routes.
- Requires mod_rewrite.
- Files are only on S3 (maybe this doesn't matter).
- Templates are mustache.php only.
- Keep the file structure flat. Not too many classes.
- Functions are cool when namespaced.
- No plugins. Just extend if you need to.
- Config in yaml.
- No built in shit like blogs or comments or user or whatever.
- Don't impose a user auth model. Use Twitter or Facebook for that.


Example
-------
It lives in index.php. Check it out.