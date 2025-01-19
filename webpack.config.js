const Encore = require('@symfony/webpack-encore');
//let dotenv = require('dotenv');

//const env = dotenv.config();
//let theme = env.parsed.APP_THEME || 'tailwind';
// ${theme}

// Manually configure the runtime environment if not already configured yet by the "encore" command.
// It's useful when you use tools that rely on webpack.config.js file.
if (!Encore.isRuntimeEnvironmentConfigured()) {
    Encore.configureRuntimeEnvironment(process.env.NODE_ENV || 'dev');
}
Encore
    .setOutputPath('public/build/')
    .setPublicPath('/build')
    .addEntry('app', `./assets/tailwind/app.js`)
    //.enableStimulusBridge(`./assets/tailwind/controllers.json`)
    .addEntry('coupon-js', `./assets/tailwind/js/order/coupon.js`)
    .addEntry('summary-js', `./assets/tailwind/js/order/summary.js`)
    .addEntry('message-js', `./assets/tailwind/js/message.js`)
    .addEntry('market-js', `./assets/tailwind/js/market.js`)
    .addEntry('operation-js', `./assets/tailwind/js/dashboard/operation.js`)
    .addEntry('image-manager-js', `./assets/tailwind/js/dashboard/image-manager.js`)
    .addEntry('dashboard-js', `./assets/tailwind/js/dashboard/index.js`)
    .addEntry('checkout-js', `./assets/tailwind/js/checkout.js`)
    .addEntry('configuration-js', `./assets/tailwind/js/dashboard/configuration.js`)
    .enablePostCssLoader((options) => {
        options.postcssOptions = {
            config: './postcss.config.js',
        }
    })
    .splitEntryChunks()

    // enables the Symfony UX Stimulus bridge (used in assets/bootstrap.js)
    .enableStimulusBridge('./assets/controllers.json')
    .enableStimulusBridge('./assets/controllers.json')
    .enableSingleRuntimeChunk()
    .cleanupOutputBeforeBuild()
    .enableBuildNotifications()
    .enableSassLoader()
    .enableSourceMaps(!Encore.isProduction())
    .enableVersioning(Encore.isProduction())
    .configureBabel((config) => {
        config.plugins.push('@babel/plugin-proposal-class-properties');
    })
    .configureBabelPresetEnv((config) => {
        config.useBuiltIns = 'usage';
        config.corejs = '3.23';
    })
;

module.exports = Encore.getWebpackConfig();