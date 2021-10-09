<?php
namespace PortierLdap\Controller;

use Psr\Http\Message\ResponseInterface as Res;
use Psr\Http\Message\ServerRequestInterface as Req;
use Respect\Validation\Validator as v;

/**
 * Controller that implements the login form.
 */
final class Login
{
    public function __construct(
        private \PortierLdap\Settings $settings,
        private \Slim\HttpCache\CacheProvider $cacheProvider,
        private \Slim\Views\Twig $twig,
        private \PortierLdap\TokenBuilder $tokenBuilder,
        private \PortierLdap\OauthResponder $oauthResponder,
    ) {
    }

    /**
     * Start of a login attempt.
     */
    public function start(Req $req, Res $res): Res
    {
        // Never cache this resource.
        $res = $this->cacheProvider->denyCache($res);

        // Get input parameters from the query.
        $input = $req->getQueryParams();

        // Validate input.
        $validation = v::arrayType()
            ->key('response_type', v::equals('id_token'))
            ->key('response_mode', v::oneOf(
                v::equals('form_post'),
                v::equals('fragment')
            ), false)
            ->key('scope', v::equals('openid email'))
            ->key('redirect_uri', v::stringType()->url())
            ->key('state', v::stringType(), false)
            ->key('nonce', v::stringType()->notEmpty())
            ->key('login_hint', v::stringType()->email());

        if (!$validation->validate($input)) {
            return $res->withStatus(400);
        }

        // Extract the username and domain from the email.
        list($username, $domain) = explode('@', $input['login_hint'], 2);

        // The domain must match our configuration.
        if ($domain !== $this->settings->domain) {
            return $res->withStatus(400);
        }

        // Render the login page.
        return $this->twig->render($res, 'login.html.twig', [
            'username' => $username,
            'domain' => $domain,
            'redirectUri' => $input['redirect_uri'],
            'responseMode' => isset($input['response_mode']) ? $input['response_mode'] : 'fragment',
            'state' => isset($input['state']) ? $input['state'] : '',
            'nonce' => $input['nonce'],
            'error' => false,
        ]);
    }

    /**
     * Complete a login.
     */
    public function complete(Req $req, Res $res): Res
    {
        // Never cache this resource.
        $res = $this->cacheProvider->denyCache($res);

        // Get the input parameters from the body.
        $input = (array) $req->getParsedBody();

        // Validate input.
        $validation = v::arrayType()
            ->key('redirect_uri', v::stringType()->url())
            ->key('response_mode', v::oneOf(
                v::equals('form_post'),
                v::equals('fragment')
            ))
            ->key('state', v::stringType())
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
            $conn = ldap_connect(implode(' ', $this->settings->ldapServers));

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
                $this->settings->dnTemplate
            );

            // Attempt to bind with the credentials given.
            $valid = @ldap_bind($conn, $dn, $password);
        }

        // If invalid, render the login page again.
        if (!$valid) {
            return $this->twig->render($res->withStatus(403), 'login.html.twig', [
                'username' => $username,
                'domain' => $this->settings->domain,
                'redirectUri' => $input['redirect_uri'],
                'responseMode' => $input['response_mode'],
                'state' => $input['state'],
                'nonce' => $input['nonce'],
                'error' => true,
            ]);
        }

        // Create a signed token.
        $token = $this->tokenBuilder->getToken(
            $username . '@' . $this->settings->domain,
            $input['redirect_uri'],
            $input['nonce']
        );

        // Return the token to the client.
        return $this->oauthResponder->createResponse(
            $res,
            $input['response_mode'],
            $input['redirect_uri'],
            $token,
            $input['state']
        );
    }
}
