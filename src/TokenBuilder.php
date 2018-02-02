<?php
namespace PortierLdap;

use Lcobucci\JWT;
use Psr\Http\Message\ResponseInterface as Res;

/**
 * Service that builds IdP tokens.
 */
final class TokenBuilder
{
    /** @var string **/
    private $origin;
    /** @var JWT\Signer\Key **/
    private $key;
    /** @var string **/
    private $kid;
    /** @var JWT\Signer **/
    private $signer;

    public function __construct(
        string $origin,
        string $key,
        string $kid
    ) {
        $this->origin = $origin;
        $this->key = new JWT\Signer\Key($key);
        $this->kid = $kid;
        $this->signer = new JWT\Signer\Rsa\Sha256;
    }

    /**
     * Create a token for the given email and return parameters.
     */
    public function getToken(string $email, string $redirectUri, string $nonce): string
    {
        // Get the origin of the redirect URI.
        // This is the token audience in Portier.
        $audience = Util::getUrlOrigin($redirectUri);

        // Build a token.
        $builder = new JWT\Builder;
        $token = $builder
            ->set('iss', $this->origin)
            ->set('aud', $audience)
            ->set('exp', time() + 60)
            ->set('nonce', $nonce)
            ->set('email', $email)
            ->setHeader('kid', $this->kid)
            ->sign($this->signer, $this->key)
            ->getToken();

        return (string) $token;
    }

    /**
     * Create a redirect response with the token attached.
     */
    public function getRedirect(Res $res, string $email, string $redirectUri, string $nonce, string $state = null): Res
    {
        // Create the redirect parameters, including the token.
        $params = [
            'id_token' => $this->getToken($email, $redirectUri, $nonce),
        ];
        if ($state !== null) {
            $params['state'] = $state;
        }

        // Build the redirect URL containing the token.
        // @todo Should we combine hash parameters?
        $url = $redirectUri . '#' . http_build_query($params);

        // Return a '303 See Other' redirect response.
        return $res->withStatus(303)->withHeader('Location', $url);
    }
}
