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

Credits
-------

- Thanks to [Marius Balčytis](https://github.com/mariusbalcytis) this package is a fork 
of his Symfony [Webpack Bundle](https://github.com/mariusbalcytis/webpack-bundle).
