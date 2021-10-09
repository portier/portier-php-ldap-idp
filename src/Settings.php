<?php
namespace PortierLdap;

/**
 * Class that holds all settings. ('plain old data')
 */
final class Settings
{
    /**
     * Email domain handled by the app.
     * For internationalized domain names (IDN), specify the punycode encoding here.
     */
    public string $domain;

    /**
     * Origin of the app. This is the public URL without a trailing slash.
     * The app cannot be hosted in a subdirectory.
     * Again, for IDN, use the punycode encoding here.
     */
    public string $origin;

    /**
     * PEM-format signing keys we use. Only the first will be used for signing.
     * Setting multiple keys is useful when rotating keys, the others are still listed in `/keys.json`.
     *
     * @var string[]
     */
    public array $keys;

    /**
     * LDAP servers to authenticate against.
     *
     * @var string[]
     */
    public array $ldapServers;

    /**
     * The template for the distinguished name to try and bind to.
     * In this template, `{USERNAME}` is replaced with the escaped username.
     */
    public string $dnTemplate;

    /**
     * Optional cache directory. The default is `_cache` in the app root directory.
     */
    public string $cacheDir = __DIR__ . '/../_cache';

    /**
     * Uncomment in development to display errors in the response.
     */
    public bool $displayErrorDetails = false;
}
