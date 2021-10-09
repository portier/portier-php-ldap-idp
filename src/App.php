<?php
namespace PortierLdap;

/**
 * Class with static methods to create the application.
 */
final class App
{
    private function __construct()
    {
    }

    /**
     * Create the Slim application.
     */
    public static function create(Settings $settings): \Slim\App
    {
        // Create cache directories.
        @mkdir($settings->cacheDir . '/views', 0777, true);

        // Create the Slim app.
        $app = new \Slim\App(
            new \Slim\Psr7\Factory\ResponseFactory(),
            new Container($settings),
        );

        // Set the router cache file.
        $app->getRouteCollector()->setCacheFile(
            $settings->cacheDir . '/routes.php'
        );

        // Add cache middleware. Set default cache lifespan to 1 day.
        $app->add(new \Slim\HttpCache\Cache('public', 86400));

        // Add standard middleware.
        $app->addRoutingMiddleware();
        $app->addErrorMiddleware($settings->displayErrorDetails, true, true);

        // Add our routes.
        $app->get('/.well-known/webfinger', 'discovery:webfinger');
        $app->get('/.well-known/openid-configuration', 'discovery:oidcConfig');
        $app->get('/keys.json', 'discovery:keys');
        $app->get('/login', 'login:start');
        $app->post('/login', 'login:complete');

        return $app;
    }
}
