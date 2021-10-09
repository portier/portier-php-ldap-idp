<?php
namespace PortierLdap;

use Slim\HttpCache\CacheProvider;
use Slim\Views\Twig;

/**
 * A simple service container.
 *
 * We have so few services, using a dependency injection library for this seems wasteful.
 */
final class Container implements \Psr\Container\ContainerInterface
{
    private ?CacheProvider $cacheProvider = null;
    private ?Twig $twig = null;
    private ?TokenBuilder $tokenBuilder = null;
    private ?OauthResponder $oauthResponder = null;
    private ?Controller\Discovery $discovery = null;
    private ?Controller\Login $login = null;

    public function __construct(
        private Settings $settings,
    ) {
    }

    public function getCacheProvider(): CacheProvider
    {
        if ($this->cacheProvider === null) {
            $this->cacheProvider = new CacheProvider();
        }

        return $this->cacheProvider;
    }

    public function getTwig(): Twig
    {
        if ($this->twig === null) {
            $this->twig = Twig::create(__DIR__ . '/../views', [
                'cache' => $this->settings->cacheDir . '/views',
            ]);
        }

        return $this->twig;
    }

    public function getTokenBuilder(): TokenBuilder
    {
        if ($this->tokenBuilder === null) {
            // Get the signing key. (The first key from configuration.)
            $keys = $this->settings->keys;

            $key = reset($keys);
            if ($key === false) {
                throw new \Exception('No keys specified in settings.php');
            }

            $kid = key($keys);
            if (!is_string($kid)) {
                throw new \Exception('Keys array in settings.php must have string keys');
            }

            $this->tokenBuilder = new TokenBuilder($this->settings->origin, $key, $kid);
        }

        return $this->tokenBuilder;
    }

    public function getOauthResponder(): OauthResponder
    {
        if ($this->oauthResponder === null) {
            $this->oauthResponder = new OauthResponder($this->getTwig());
        }

        return $this->oauthResponder;
    }

    public function getDiscovery(): Controller\Discovery
    {
        if ($this->discovery === null) {
            $this->discovery = new Controller\Discovery($this->settings);
        }

        return $this->discovery;
    }

    public function getLogin(): Controller\Login
    {
        if ($this->login === null) {
            $this->login = new Controller\Login(
                $this->settings,
                $this->getCacheProvider(),
                $this->getTwig(),
                $this->getTokenBuilder(),
                $this->getOauthResponder(),
            );
        }

        return $this->login;
    }

    // ContainerInterface implementation.

    public function get(string $id)
    {
        $method = 'get' . ucfirst($id);
        $callable = [$this, $method];
        assert(is_callable($callable));

        return call_user_func($callable);
    }

    public function has(string $id): bool
    {
        $method = 'get' . ucfirst($id);

        return method_exists($this, $method);
    }
}
