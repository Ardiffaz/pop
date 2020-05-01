const
    Encore = require('@symfony/webpack-encore'),
    HtmlWebpackPlugin = require('html-webpack-plugin')
;

Encore
    .setOutputPath('public/build/')
    .enableVersioning(Encore.isProduction())
    .setPublicPath('/build')
    .enableSingleRuntimeChunk()
    .cleanupOutputBeforeBuild()
    .enableSourceMaps(false)
    .addEntry('main', './frontend/main.js')
    .enableVueLoader()
    .enableLessLoader()
    .addPlugin(new HtmlWebpackPlugin({
        title: 'Welcome!',
        filename: '../index.html',
        favicon: './public/favicon.png',
        meta: {
            viewport: 'width=940',
        },
    }))
;

const config = Encore.getWebpackConfig();
config.resolve.symlinks = false;

module.exports = config;
