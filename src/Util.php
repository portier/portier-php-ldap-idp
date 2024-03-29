<?php
namespace PortierLdap;

use Base64Url\Base64Url;

/**
 * Misc utilities. Contains only static methods.
 */
final class Util
{
    private function __construct()
    {
    }

    /**
     * Convert a PEM private key to a JWK public key.
     *
     * @return array<string, string>
     */
    public static function pemToJwk(string $kid, string $pem): array
    {
        $pubkey = openssl_get_privatekey($pem);
        if ($pubkey === false) {
            throw new \Exception(sprintf('Failed to parse key "%s"', $kid));
        }

        $parts = openssl_pkey_get_details($pubkey);
        if ($parts === false) {
            throw new \Exception(sprintf('Failed to inspect key "%s"', $kid));
        }

        return [
            'kid' => $kid,
            'kty' => 'RSA',
            'alg' => 'RS256',
            'use' => 'sig',
            'n' => Base64Url::encode($parts['rsa']['n']),
            'e' => Base64Url::encode($parts['rsa']['e']),
        ];
    }

    /**
     * Extract the origin from an URL.
     */
    public static function getUrlOrigin(string $url): string
    {
        $parts = parse_url($url);
        if ($parts === false || empty($parts['scheme']) || empty($parts['host'])) {
            throw new \Exception('Invalid URI');
        }

        $scheme = $parts['scheme'];
        $host = $parts['host'];

        $origin = $scheme . '://' . $host;
        if (isset($parts['port'])) {
            $port = $parts['port'];
            if (($scheme === 'http' && $port !== 80) ||
                    ($scheme === 'https' && $port !== 443)) {
                $origin .= ':' . $port;
            }
        }

        return $origin;
    }
}
