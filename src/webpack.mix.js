const mix = require('laravel-mix');

/*
 |--------------------------------------------------------------------------
 | Mix Asset Management
 |--------------------------------------------------------------------------
 |
 | Mix provides a clean, fluent API for defining some Webpack build steps
 | for your Laravel applications. By default, we are compiling the CSS
 | file for the application as well as bundling up all the JS files.
 |
 */

// Main admin application
mix.js('resources/js/index.js', 'public/js')
    .react()
    .sourceMaps()
    .css('resources/css/app.css', 'public/css');

// WebRTC Dial Widget - standalone bundle for embedding
mix.js('resources/js/widget/index.js', 'public/js/dial-widget.js')
    .react()
    .sourceMaps();

// Webpack 5 no longer includes Node.js polyfills by default
mix.webpackConfig({
    resolve: {
        fallback: {
            buffer: false,
        },
    },
    output: {
        // Ensure the widget exports are available globally
        library: {
            name: 'YapDialWidget',
            type: 'umd',
            export: 'default',
        },
    },
});
