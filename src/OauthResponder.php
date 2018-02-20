<?php
namespace PortierLdap;

use Slim\Views\Twig as TwigService;
use Psr\Http\Message\ResponseInterface as Res;

/**
 * Handles OAuth response modes, generating the correct response.
 */
final class OauthResponder
{
    /** @var TwigService **/
    private $view;

    public function __construct(TwigService $view)
    {
        $this->view = $view;
    }

    /**
     * Create a response based on the response mode parameter.
     */
    public function createResponse(
        Res $res,
        string $responseMode,
        string $redirectUri,
        string $token,
        string $state
    ): Res {
        switch ($responseMode) {
            case 'form_post':
                return $this->createFormPostResponse($res, $redirectUri, $token, $state);
            case 'fragment':
                return $this->createFragmentResponse($res, $redirectUri, $token, $state);
            default:
                throw new \RuntimeException(sprintf(
                    "Unsupported response mode '%s'",
                    $responseMode
                ));
        }
    }

    /**
     * Create a `form_post` response.
     */
    public function createFormPostResponse(
        Res $res,
        string $redirectUri,
        string $token,
        string $state
    ): Res {
        // Render a form which does the POST request for us.
        return $this->view->render($res, 'redirect.html.twig', compact('redirectUri', 'token', 'state'));
    }

    /**
     * Create a `fragment` response.
     */
    public function createFragmentResponse(
        Res $res,
        string $redirectUri,
        string $token,
        string $state
    ): Res {
        // Build the fragment parameters.
        $params = ['id_token' => $token];
        if ($state) {
            $params['state'] = $state;
        }
        $fragment = http_build_query($params);

        // Append to the URL, which may contain existing parameters.
        $parts = parse_url($redirectUri);
        if (empty($parts['fragment'])) {
            $parts['fragment'] = $fragment;
        } else {
            $parts['fragment'] .= '&' . $fragment;
        }

        // Redirect to the complete URL.
        return $res
            ->withStatus(303)
            ->withHeader('Location', http_build_url($parts));
    }
}
