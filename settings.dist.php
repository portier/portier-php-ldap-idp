<?php

// See `src/Settings.php` for documentation on all available settings.
$settings = new \PortierLdap\Settings;

$settings->domain = 'mydomain.example';
$settings->origin = 'https://auth.mydomain.example';

$settings->ldapServers = ['ldap://dc.mydomain.example'];
$settings->dnTemplate = 'uid={USERNAME},cn=users,dc=mydomain,dc=example';

$mainKey = file_get_contents(__DIR__ . '/key.pem');
if ($mainKey === false) {
    throw new \Exception('Could not read key.pem');
}
$settings->keys = [$mainKey];

return $settings;
