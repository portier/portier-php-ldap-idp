<?php
return [
    // Email domain handled by the app.
    // For internationalized domain names (IDN), specify the punycode encoding here.
    'domain' => 'mydomain.example',

    // Origin of the app. This is the public URL without a trailing slash.
    // The app cannot be hosted in a subdirectory.
    // Again, for IDN, use the punycode encoding here.
    'origin' => 'https://auth.mydomain.example',

    // PEM-format signing keys we use. Only the first will be used for signing.
    // Setting multiple keys is useful when rotating keys, the others are still listed in `/keys.json`.
    'keys' => [
        'main' => file_get_contents(__DIR__ . '/key.pem'),
    ],

    // LDAP servers to authenticate against.
    'ldapServers' => [
        'ldap://dc.mydomain.example',
    ],

    // The template for the distinguished name to try and bind to.
    // In this template, `{USERNAME}` is replaced with the escaped username.
    'dnTemplate' => 'uid={USERNAME},cn=users,dc=mydomain,dc=example',

    // Optional cache directory. The default is `_cache` in the app root directory.
    // 'cacheDir' => '/path/to/cache/directory',

    // Uncomment in development to display errors in the response.
    // 'displayErrorDetails' => true,
];
