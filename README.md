Package to Integrate Webpack into Laravel
====

Package to help integrating [webpack](https://webpack.js.org/) into Laravel project.

What is webpack?
----

Module bundler and CommonJS / AMD dependency manager.

For me, it replaces both grunt/gulp and RequireJS.

What does this package do?
----

1. Finds javascript entry points inside your blade templates.
2. Runs webpack with [assets-webpack-plugin](https://github.com/sporto/assets-webpack-plugin).
3. Saves generated file names, so that twig function returns correct URL to generated asset.

Additionally, for development environment:

1. Runs [webpack-dev-server](https://webpack.js.org/configuration/dev-server/), which serves and regenerates assets if they are changed.
2. Watches twig templates for changes, updates entry points and
restarts webpack-dev-server if webpack configuration changes.

More goodies:
1. Lets you configure webpack config as you want, while still providing needed parameters from Laravel, like
entry points, aliases, environment and additional parameters.
2. Lets you define custom entry point providers if you don't use twig or include scripts in any other way.
3. Works with images and css/less/sass files out-of-the-box, if needed.
4. Supports both Webpack 2 (by default) and Webpack 1.

Installation
----

```shell
composer require zquintana/laravel-webpack
```

Add service provider to your app config.

```php
ZQuintana\LaravelWebpack\WebpackServiceProvider::class,
```

If you want to use the facade in instead of the helper, add this to your facades in `app.php`:

```php
'Webpack' => ZQuintana\LaravelWebpack\Facade\Webpack::class,
```

Run command:

```bash
php artisan zq:webpack:setup
```

It copies default `webpack.config.js` and `package.json` files and runs `npm install`.

If any of the files already exists, you'll be asked if you'd like to overwrite them.

`webpack.config.js` must export a function that takes `options` as an argument and returns webpack config.

Feel free to modify this configuration file as you'd like - bundle just provides default one as a starting point.

You should add `webpack.config.js` and `package.json` into your repository. You should also add `node_modules` into
`.gitignore` file and run `npm install` similarly to `composer install` (after cloning repository, after `package.json`
is updated and as a task in your deployment). Of course, you could just add it to your repository, too.

```bash
git add package.json config/webpack.config.js
```

If you want to use Webpack 1 for some reason, pass `--useWebpack1` as a command line option to `setup` command.

Usage
----

The helpers look for entry points in your `resources/js` directory by default.

Inside blade templates:

```twig
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    
    <link rel="stylesheet" href="{{ webpack_asset('application.js') }}"/>
</head>
<body>
    
    <img src="{{ webpack_asset('funny-kitten.png') }}"/>
</body>
</html>
```

Inside `resources/js/application.js`:

```js
require('./script2.js');
require('./my-styles.less');

function loadScript3() {
    require.ensure([], function() {
        require('./style.css');
    });
}
setTimeout(loadScript3, 1000);
```

As part of deployment into production environment, after clearing the cache:

```bash
php artisan zq:webpack:compile --env=prod
```

In development environment (this must always run in the background):

```bash
php artisan zq:webpack:dev-server
```

Alternatively, if you are not actively developing your frontend, you can compile once and
forget about it, similarly to production environment:

```bash
php artisan zq:webpack:compile
```
If you are running functional tests on your app, make sure to compile once for test environment to generate manifest file for test environment:

```bash
php artisan zq:webpack:compile --env=test
```

Helper function
----

Function:
```
webpack_asset(resource, type = null)
```

`type` is `js` or `css`, leave `null` to guess the type. For `css` this function could return `null` if no CSS would
be extracted from provided entry point. If you are sure that there will be some CSS, you could just ignore this.

As with function, provide `js`, `css` or leave it out to guess the type.

See usage with `named` and `group` in [Using commons chunk](#using-commons-chunk) section.

Keep in mind that you must provide hard-coded asset paths in both tag and function.
This is to find all available assets in compile-time.

Stylesheets
----

By default, [ExtractTextPlugin](https://github.com/webpack-contrib/extract-text-webpack-plugin) is configured. This means
that if you `require` any file that compiles to CSS (`.css`, `.less`, `.scss`) it is removed from compiled JS file
and stored into a separate one. So you have to include it explicitly.

Keep in mind that when you are providing entry point - it's still usually `.js` file (see usage example).

If you want to disable this functionality so that CSS would be loaded together with JS in a single request,
disable `extract_css`:

```php
// config/zq_webpack.php
...

    'webpack' => [
        'config_parameters' => [
            'extract_css': false,
        ],
    ],
```

This plugin is also needed if you want to require css/less/sass files directly as an entry point.

ES6, Less and Sass support
----
ES6, Less and Sass works out of the box:

- use `.js` or `.jsx` extension to compile from ES6 and ES7 to ES5 using [Babel](https://babeljs.io/);
- use `.less` extension to compile [Less](http://lesscss.org/) files;
- use `.scss` extension to compile [Sass](http://sass-lang.com/) files.

If you need any custom loaders, feel free to install them via `npm` and modify `app/config/webpack.config.js` if needed.

Loading images
----
Images are optimized by default using [image-webpack-loader](https://github.com/tcoopman/image-webpack-loader).

You can include images directly into your twig templates by using the same `webpack_asset` function.

For this to work correctly, loader for image files must remain `file` in your webpack configuration.

```twig
<img src="{{ webpack_asset('images/cat.png') }}"/>
```

Of course, you can use them in your CSS, too:

```css
.cat {
    /* cat.png will be optimized and copied to compiled directory with hashed file name */
    /* URL to generated image file will be in the css output  */
    background: url("images/cat.png")
}
```

If you are providing webpack-compatible asset path in CSS, prefix it with `~`. Use relative paths as usual.
See [css-loader](https://github.com/webpack/css-loader) for more information.

Aliases
----

Aliases are prefixed with `@` and point to some specific path.

Aliases work the same in both twig templates (parameter to `webpack_asset` function) and Javascript files
(parameter to `require` or similar Webpack provided function).

By default, these aliases are registered:

- `@root`, which points to `base_path()` (usually the root of your repository)

You can also register your own aliases, for example `@bower` or `@npm`
would be great candidates if you use any of those package managers. Or something like `@vendor`
if you use composer to install your frontend assets:

```php
// config/zq_webpack.php
...

    'aliases' => [
        'npm'    => base_path('node_modules'),     # or any other path where assets are installed
        'bower'  => base_path('bower'),
        'vendor' => base_path('vendor'),
    ],
```

Inside your JavaScript files:

```js
var $ = require('@npm/jquery');
```

Be sure to install dependencies (either npm, bower or any other) on path not directly accessible from web.
This is not needed by webpack (it compiles them - they can be anywhere on the system) and could cause a security
flaw (some assets contain backend examples, which could be potentially used in your production environment).

## Configuring dev-server

`php artisan zq:webpack:dev-server` runs webpack-dev-server as a separate process,
it listens on `localhost:8080`. By default, assets in development
environment are pointed to `//localhost:8080/compiled/*`.

If you run this command inside VM, docker container etc., configure
`maba_webpack.config.parameters.dev_server_public_path` to use correct host. Also, as
dev-server listens only to localhost connections by default, add this to configuration:

```php
// config/zq_webpack.php
...
    'bin' => [
        'dev_server' => [
            'executable' => ['node_modules/.bin/webpack-dev-server'],
            'arguments'  => [
                '--hot',                   # these are default options - leave them if needed
                '--history-api-fallback',
                '--inline',
                '--host',                  # let's add host option
                '0.0.0.0'                  # each line is escaped, so option comes in it's own line
                '--public'                 # this is also needed from webpack-dev-server 2.4.3
                'dev-server-host.dev:8080' # change to whatever host you are using
            ],
        ],
    ],
    'webpack' => [
        'config_parameters' => [
            'dev_server_public_path' => '//dev-server-host.dev:8080/compiled/',
        ],
    ],
```

If you need to provide different port, be sure to put `--port` and the port itself into separate lines.

When compiling assets with `webpack-dev-server`, [webpack-dashboard](https://github.com/FormidableLabs/webpack-dashboard)
is used for more user-friendly experience. You can disable it by setting `tty_prefix` option to `[]`.
You can also remove `DashboardPlugin` in such case from `webpack.config.js`.

## Configuring memory for Node.js

If you are experiencing "heap out of memory" error when running `php artisan zq:webpack:compile`
and/or `php artisan zq:webpack:dev-server`, try to give more memory for Node.js process:

```php
// config/zq_webpack.php
...
    'bin' => [
        'webpack' => [        # same with dev_server
            'executable' => [
                'node',
                '--max-old-space-size=4096',
                'node_modules/webpack/bin/webpack.js',
            ],
        ],
    ],
```

Using commons chunk
----

This bundle supports both single and several
[commons chunks](https://webpack.js.org/plugins/commons-chunk-plugin/),
but you have to configure this explicitly.

In your `webpack.config.js`:

```js
config.plugins.push(
    new webpack.optimize.CommonsChunkPlugin({
        name: 'commons'
    })
);
```

In your base template:

```twig
<link rel="stylesheet" href="{{ webpack_named_asset('commons', 'css') }}"/>
{# ... #}
<script src="{{ webpack_named_asset('commons', 'js') }}"></script>
```

Credits
-------

- Thanks to [Marius Balčytis](https://github.com/mariusbalcytis) this package is a fork 
of his Symfony [Webpack Bundle](https://github.com/mariusbalcytis/webpack-bundle).
