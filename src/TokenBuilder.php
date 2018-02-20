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
            ->set('iat', time())
            ->set('email', $email)
            ->set('nonce', $nonce)
            ->setHeader('kid', $this->kid)
            ->sign($this->signer, $this->key)
            ->getToken();

        return (string) $token;
    }
}
