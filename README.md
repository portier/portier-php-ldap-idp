# portier-php-ldap-idp

A [Portier] identity provider that authenticates against an LDAP server.

 [Portier]: https://portier.github.io/

### Installation

To run this app, you need at least PHP 7.0 with the `gmp` extension. [Composer]
is used to manage dependencies.

This app must be deployed on a webserver that is accessible by your users over
HTTPS, and also able to connect to your LDAP server.

It currently must have its own HTTPS domain, and cannot be hosted in a
subdirectory. The domain doesn't have to be the exact domain for which you're
trying to authenticate email addresses, though.

 - [Download a ZIP] of the app or clone the [git repository].

 - Install the dependencies:

```bash
composer install --no-dev
```

 - Create an RSA private key to sign tokens with:

```bash
openssl genrsa -out key.pem 4096
```

 - Create a `settings.php` based on `settings.dist.php`.

```bash
cp settings.dist.php settings.php
$EDITOR settings.php
```

 - See the [Slim webserver documentation] on configuring your webserver.
   (Similar to most other PHP apps, with `public/` as the document root.)

 - If the app is not hosted on the exact email domain, configure Webfinger on
   the HTTPS email domain. The simplest way to do this is to serve a static file
   at `https://mydomain.example/.well-known/webfinger` containing, for example:

```json
{
  "links": [
    {
      "rel": "https://portier.io/specs/auth/1.0/idp",
      "href": "https://auth.mydomain.example"
    }
  ]
}
```

 [Composer]: https://getcomposer.org/
 [Download a ZIP]: https://github.com/portier/portier-php-ldap-idp/archive/master.zip
 [git repository]: https://github.com/portier/portier-php-ldap-idp
 [Slim webserver documentation]: https://www.slimframework.com/docs/start/web-servers.html

### Hardening

Rate limit requests in your webserver (specifically POST requests) to prevent
brute forcing of passwords. (Your LDAP server may also be rate limiting login
attempts already, though you should not rely on this.)

Rotate keys regularly. Due to caching, you should not do this more than once a
day. You may use the included `rotate-keys.sh` script, see the comments in it
for more information.
