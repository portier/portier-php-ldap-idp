<?php
namespace PortierLdap\Controller;

use Psr\Http\Message\ResponseInterface as Res;
use Psr\Http\Message\ServerRequestInterface as Req;

/**
 * Controller that implements discovery routes.
 */
final class Discovery
{
    public function __construct(
        private \PortierLdap\Settings $settings,
    ) {
    }

    /**
     * Implement Webfinger, in case we're running on the actual email domain.
     */
    public function webfinger(Req $req, Res $res): Res
    {
        return $this->json($res, [
            'links' => [
                [
                    'rel' => 'https://portier.io/specs/auth/1.0/idp',
                    'href' => $this->settings->origin,
                ],
            ],
        ]);
    }

    /**
     * Serve our OpenID configuration document.
     */
    public function oidcConfig(Req $req, Res $res): Res
    {
        $origin = $this->settings->origin;

        return $this->json($res, [
            'issuer' => $origin,
            'authorization_endpoint' => $origin . '/login',
            'response_modes_supported' => ['form_post', 'fragment'],
            'jwks_uri' => $origin . '/keys.json',
        ]);
    }

    /**
     * Serve our public keys used to sign tokens.
     */
    public function keys(Req $req, Res $res): Res
    {
        $keys = [];
        foreach ($this->settings->keys as $kid => $pem) {
            $keys[] = \PortierLdap\Util::pemToJwk($kid, $pem);
        }

        return $this->json($res, compact('keys'));
    }

    private function json(Res $res, mixed $content): Res
    {
        $res->getBody()->write(json_encode($content, JSON_THROW_ON_ERROR));

        return $res->withHeader('Content-Type', 'application/json');
    }
}
