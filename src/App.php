<?php
namespace PortierLdap;

use Respect\Validation\Validator as v;
use Slim\Http\Request as Req;
use Slim\Http\Response as Res;

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
    public static function create(array $settings): \Slim\App
    {
        // Create cache directories.
        @mkdir($settings['cacheDir'] . '/views', 0777, true);

        // Set the router cache file.
        $settings['routerCacheFile'] = $settings['cacheDir'] . '/routes.php';

        // Create the Slim app.
        $app = new \Slim\App([
            'settings' => $settings,

            // Add the service that'll help us with cache headers.
            'cache' => function () {
                return new \Slim\HttpCache\CacheProvider();
            },

            // Add the Twig rendering service.
            'view' => function ($container) {
                $settings = $container->settings;

                return new \Slim\Views\Twig(__DIR__ . '/../views', [
                    'cache' => $settings['cacheDir'] . '/views',
                ]);
            },

            // Add our token builder service.
            'tokenBuilder' => function ($container) {
                $settings = $container->settings;

                // Get the signing key. (The first key from configuration.)
                $keys = $settings['keys'];
                $key = reset($keys);
                $kid = key($keys);

                return new TokenBuilder($settings['origin'], $key, $kid);
            },
        ]);

        // Default cache for one day.
        $app->add(new \Slim\HttpCache\Cache('public', 86400));

        // Add our routes.
        self::addRoutes($app);

        return $app;
    }

    /**
     * Add the routes to the app.
     *
     * @return void
     */
    private static function addRoutes(\Slim\App $app)
    {
        // Implement Webfinger, in case we're running on the actual email domain.
        $app->get('/.well-known/webfinger', function (Req $req, Res $res) {
            return $res->withJson([
                'links' => [
                    [
                        'rel' => 'https://portier.io/specs/auth/1.0/idp',
                        'href' => $this->settings['origin'],
                    ],
                ],
            ]);
        });

        // Serve our OpenID configuration document.
        $app->get('/.well-known/openid-configuration', function (Req $req, Res $res) {
            $origin = $this->settings['origin'];

            return $res->withJson([
                'issuer' => $origin,
                'authorization_endpoint' => $origin . '/login',
                'jwks_uri' => $origin . '/keys.json',
            ]);
        });

        // Serve our public keys used to sign tokens.
        $app->get('/keys.json', function (Req $req, Res $res) {
            $keys = [];
            foreach ($this->settings['keys'] as $kid => $pem) {
                $keys[] = Util::pemToJwk($kid, $pem);
            }

            return $res->withJson(compact('keys'));
        });

        // Start of a login attempt.
        $app->get('/login', function (Req $req, Res $res) {
            // Never cache this resource.
            $res = $this->cache->denyCache($res);

            // Get input parameters from the query.
            $input = $req->getQueryParams();

            // Validate input.
            $validation = v::arrayType()
                ->key('response_type', v::equals('id_token'))
                ->key('scope', v::equals('openid email'))
                ->key('redirect_uri', v::stringType()->url())
                ->key('state', v::stringType())
                ->key('nonce', v::stringType()->notEmpty())
                ->key('login_hint', v::stringType()->email());

            if (!$validation->validate($input)) {
                return $res->withStatus(400);
            }

            // Extract the username and domain from the email.
            list($username, $domain) = explode('@', $input['login_hint'], 2);

            // The domain must match our configuration.
            if ($domain !== $this->settings['domain']) {
                return $res->withStatus(400);
            }

            // Render the login page.
            return $this->view->render($res, 'login.html.twig', [
                'username' => $username,
                'domain' => $domain,
                'redirectUri' => $input['redirect_uri'],
                'state' => isset($input['state']) ? $input['state'] : '',
                'nonce' => $input['nonce'],
                'error' => false,
            ]);
        });

        // Complete a login.
        $app->post('/login', function (Req $req, Res $res) {
            // Never cache this resource.
            $res = $this->cache->denyCache($res);

            // Get the input parameters from the body.
            $input = $req->getParsedBody();

            // Validate input.
            $validation = v::arrayType()
                ->key('redirect_uri', v::stringType()->url())
                ->key('state', v::stringType(), false)
                ->key('nonce', v::stringType()->notEmpty())
                ->key('username', v::stringType()->notEmpty())
                ->key('password', v::stringType());

            if (!$validation->validate($input)) {
                return $res->withStatus(400);
            }

            // Extract credentials.
            $username = $input['username'];
            $password = $input['password'];

            // Password cannot be empty.
            // This is not in the validation, so that we don't 400 on a user mistake.
            $valid = !empty($password);
            if ($valid) {
                // Create the LDAP connection.
                $conn = ldap_connect(implode(' ', $this->settings['ldapServers']));

                if ($conn === false) {
                    return $res->withStatus(503);
                }

                // Set the LDAP protocol version.
                // @todo Should we make this configurable?
                ldap_set_option($conn, LDAP_OPT_PROTOCOL_VERSION, 3);

                // Create a distinguished name to bind to from the template.
                $dn = str_replace(
                    '{USERNAME}',
                    ldap_escape($username, '', LDAP_ESCAPE_DN),
                    $this->settings['dnTemplate']
                );

                // Attempt to bind with the credentials given.
                $valid = @ldap_bind($conn, $dn, $password);
            }

            if ($valid) {
                // Create a redirect response with a token.
                return $this->tokenBuilder->getRedirect(
                    $res,
                    $username . '@' . $this->settings['domain'],
                    $input['redirect_uri'],
                    $input['nonce'],
                    $input['state']
                );
            } else {
                // If invalid, render the login page again.
                return $this->view->render($res->withStatus(403), 'login.html.twig', [
                    'username' => $username,
                    'domain' => $this->settings['domain'],
                    'redirectUri' => $input['redirect_uri'],
                    'state' => isset($input['state']) ? $input['state'] : '',
                    'nonce' => $input['nonce'],
                    'error' => true,
                ]);
            }
        });
    }
}
