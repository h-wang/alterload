# Alterload for Composer

## Why Alterload

Alterload is an alternative loader for autoload.

Alterload is great for library developers.
When developing a PHP project/application we use composer to manage dependencies. Sometimes we need to work on a dependency of the project but still need to debug/test in the project. Think of a library that's part of the project.

Before Alterload there were 2 things we could do to debug/test the library in the project:

1. Commit new version of the library for all changes, wait for packagist to re-index, and update your composer.lock in the calling project and test.
2. Add a "repository" to your calling project's composer.json (which you shouldn't forget to remove during commit, and put back after)

__ * Symlinking or directly edit files in the vendor directory is not to discuss __

Alterload is inspired by https://github.com/linkorb/autotune

## How does it work

Making your application ready for Alterload takes 3 simple steps:

### 1. Include `hongliang/alterload` from Packagist in your composer.json file

```json
require-dev": {
   "hongliang/alterload": "~1.0"
}
```

### 2. Initialize Alterload in your app

Somewhere in your application, you're including `vendor/autoload.php`. Sometimes it's in `web/index.php` or `bin/console`. Find this location, and modify add these lines:

```php
$loader = require_once __DIR__.'/../vendor/autoload.php';
if (class_exists('Alterload\Loader')) {
    \Alterload\Loader::alter($loader);
}
```
Wrapping the call to `alter` in the `class_exists` block ensures alterload is only used if Alterload is installed in your (development) environment (installed from the require-dev block in composer.json). In production environments it won't be called if you install your dependencies with `--no-dev`)

### 3. Add an `.alterload.ini` file to your project root.

```ini
psr-4:Monolog\Logger\ = /Users/me/git/monolog/monolog/src/Monolog
psr-0:Monolog\Logger\ = /Users/me/git/monolog/monolog/src/Monolog/Monolog/Monolog
;psr-4:Monolog\Logger\ = /this/is/commentted/out

```

Ideally you'd add the `.alterload.ini` to your `.gitignore` file.

### Done

Whenever your application is doing something like the following, it will load the "local" version of a library, instead of the one in your `vendor/` directory.

```php
$logger = new \Monolog\Logger('example');
```

So from now on, no changes are required to your main application. Everything is managed by your local `.alterload.ini` file.

## Symlink a dependency
Sometimes we also want to use other assets (templates, js, images,...) in the depending library instead of only the PHP classes. In this case we can use the `vendor/bin/alterload link` command to symlink a library (in the `vendor` directory) to the local library.

Simply add `link:` in front of the `.alterload.ini` line: 
```
link:psr-4:Monolog\Logger\ = /Users/me/git/monolog/monolog/src/Monolog
```
Then run the command from your application directory:
```
vendor/bin/alterload link
```

## License

MIT (see [LICENSE.md](LICENSE.md))
