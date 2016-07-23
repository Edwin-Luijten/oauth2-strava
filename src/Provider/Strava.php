<?php

namespace League\OAuth2\Client\Provider;

use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Token\AccessToken;
use Psr\Http\Message\ResponseInterface;

class Strava extends AbstractProvider
{
    /**
     * @var string
     */
    const BASE_STRAVA_URL = 'https://www.strava.com';

    /**
     * @var string
     */
    protected $apiVersion = 'v3';

    /**
     * Constructs an OAuth 2.0 service provider.
     *
     * @param array $options An array of options to set on this provider.
     *     Options include `clientId`, `clientSecret`, `redirectUri`, `state`, `apiVersion`.
     *     Individual providers may introduce more options, as needed.
     * @param array $collaborators An array of collaborators that may be used to
     *     override this provider's default behavior. Collaborators include
     *     `grantFactory`, `requestFactory`, `httpClient`, and `randomFactory`.
     *     Individual providers may introduce more collaborators, as needed.
     */
    public function __construct(array $options = [], array $collaborators = [])
    {
        parent::__construct($options, $collaborators);

        foreach ($options as $option => $value) {
            if (property_exists($this, $option)) {
                $this->{$option} = $value;
            }
        }
    }
    /**
     * Get authorization url to begin OAuth flow
     *
     * @return string
     */
    public function getBaseAuthorizationUrl()
    {
        return self::BASE_STRAVA_URL . '/oauth/authorize';
    }

    /**
     * Get access token url to retrieve token
     *
     * @param  array $params
     *
     * @return string
     */
    public function getBaseAccessTokenUrl(array $params)
    {
        return self::BASE_STRAVA_URL . '/oauth/token';
    }

    /**
     * Get provider url to fetch user details
     *
     * @param  AccessToken $token
     *
     * @return string
     */
    public function getResourceOwnerDetailsUrl(AccessToken $token)
    {
        return self::BASE_STRAVA_URL . '/api/' . $this->apiVersion . '/athlete?access_token=' . $token;
    }

    /**
     * @link http://strava.github.io/api/v3/oauth/#get-authorize
     * Get the default scopes used by this provider.
     *
     * This should not be a complete list of all scopes, but the minimum
     * required for the provider user interface!
     *
     * @return array
     */
    protected function getDefaultScopes()
    {
        return ['public'];
    }

    /**
     * Check a provider response for errors.
     *
     * @throws IdentityProviderException
     * @param  ResponseInterface $response
     * @param  string $data Parsed response data
     * @return void
     */
    protected function checkResponse(ResponseInterface $response, $data)
    {
        if ($response->getStatusCode() >= 400) {
            throw new IdentityProviderException(
                $data['message'] ?: $response->getReasonPhrase(),
                $response->getStatusCode(),
                $response
            );
        }
    }

    /**
     * Generate a user object from a successful user details request.
     *
     * @param array $response
     * @param AccessToken $token
     * @return \League\OAuth2\Client\Provider\ResourceOwnerInterface
     */
    protected function createResourceOwner(array $response, AccessToken $token)
    {
        return new StravaResourceOwner($response);
    }

    /**
     * Returns the authorization headers used by this provider.
     *
     * Typically this is "Bearer" or "MAC". For more information see:
     * http://tools.ietf.org/html/rfc6749#section-7.1
     *
     * No default is provided, providers must overload this method to activate
     * authorization headers.
     *
     * @param  mixed|null $token Either a string or an access token instance
     * @return array
     */
    protected function getAuthorizationHeaders($token = null)
    {
        return [
            'Accept'          => 'application/json',
            'Authorization'   => 'Bearer ' . $token,
            'Accept-Encoding' => 'gzip',
        ];
    }

    /**
     * @return string
     */
    public function getBaseStravaUrl()
    {
        return self::BASE_STRAVA_URL;
    }

    /**
     * @return string
     */
    public function getApiVersion()
    {
        return $this->apiVersion;
    }

}
