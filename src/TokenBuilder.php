<?php
namespace PortierLdap;

use Lcobucci\JWT;
use Lcobucci\JWT\Signer\Key\InMemory;

/**
 * Service that builds IdP tokens.
 */
final class TokenBuilder
{
    public function __construct(
        private string $origin,
        private string $key,
        private string $kid,
    ) {
    }

    /**
     * Create a token for the given email and return parameters.
     */
    public function getToken(string $email, string $redirectUri, string $nonce): string
    {
        // Get the origin of the redirect URI.
        // This is the token audience in Portier.
        $audience = Util::getUrlOrigin($redirectUri);

        // Prepare timestamps.
        $issuedAt = new \DateTimeImmutable();
        $expiresAt = $issuedAt->add(new \DateInterval('PT60S'));

        // Build a token.
        $signer = new JWT\Signer\Rsa\Sha256;
        $key = InMemory::plainText($this->key);
        $config = JWT\Configuration::forAsymmetricSigner($signer, $key, $key);

        $token = $config->builder()
            ->issuedBy($this->origin)
            ->permittedFor($audience)
            ->issuedAt($issuedAt)
            ->expiresAt($expiresAt)
            ->withClaim('email', $email)
            ->withClaim('nonce', $nonce)
            ->withHeader('kid', $this->kid)
            ->getToken($config->signer(), $config->signingKey());

        return $token->toString();
    }
}
