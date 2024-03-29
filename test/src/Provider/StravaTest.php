<?php

namespace League\OAuth2\Client\Test\Provider;

use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class StravaTest extends TestCase
{
    protected $provider;
    protected $apiVersion = 'v3';

    protected function getProvider()
    {
        return new \League\OAuth2\Client\Provider\Strava(
            [
                'clientId' => 'mock_client_id',
                'clientSecret' => 'mock_secret',
                'redirectUri' => 'none',
            ]
        );
    }

    public function testAuthorizationUrl()
    {
        $provider = $this->getProvider();

        $url = $provider->getAuthorizationUrl();
        $uri = parse_url($url);
        parse_str($uri['query'], $query);
        $this->assertArrayHasKey('client_id', $query);
        $this->assertArrayHasKey('redirect_uri', $query);
        $this->assertArrayHasKey('state', $query);
        $this->assertArrayHasKey('scope', $query);
        $this->assertArrayHasKey('response_type', $query);
        $this->assertArrayHasKey('approval_prompt', $query);
        $this->assertNotNull($provider->getState());
    }

    public function testScopes()
    {
        $provider = $this->getProvider();

        $url = $provider->getAuthorizationUrl(['scope' => ['foo', 'bar']]);
        parse_str(parse_url($url, PHP_URL_QUERY), $qs);

        $this->assertArrayHasKey('scope', $qs);
        $this->assertSame('foo,bar', $qs['scope']);
    }

    public function testGetAuthorizationUrl()
    {
        $provider = $this->getProvider();

        $url = $provider->getAuthorizationUrl();
        $uri = parse_url($url);
        $this->assertEquals('/oauth/authorize', $uri['path']);
    }

    public function testGetBaseAccessTokenUrl()
    {
        $provider = $this->getProvider();

        $params = [];
        $url = $provider->getBaseAccessTokenUrl($params);
        $uri = parse_url($url);
        $this->assertEquals('/oauth/token', $uri['path']);
    }

    public function testGetAccessToken()
    {
        $response = m::mock('Psr\Http\Message\ResponseInterface');
        $response->shouldReceive('getBody')->andReturn(
            '{"access_token":"mock_access_token", "scope":"repo,gist", "token_type":"bearer"}'
        );
        $response->shouldReceive('getHeader')->andReturn(['content-type' => 'json']);
        $response->shouldReceive('getStatusCode')->andReturn(200);

        $client = m::mock('GuzzleHttp\ClientInterface');
        $client->shouldReceive('send')->times(1)->andReturn($response);

        $provider = $this->getProvider();
        $provider->setHttpClient($client);
        $token = $provider->getAccessToken('authorization_code', ['code' => 'mock_authorization_code']);

        $this->assertEquals('mock_access_token', $token->getToken());
        $this->assertNull($token->getExpires());
        $this->assertNull($token->getRefreshToken());
        $this->assertNull($token->getResourceOwnerId());
    }

    public function testStravaDomainUrls()
    {
        $provider = new \League\OAuth2\Client\Provider\Strava([
            'apiVersion' => $this->apiVersion,
        ]);

        $response = m::mock('Psr\Http\Message\ResponseInterface');
        $response->shouldReceive('getBody')->times(1)->andReturn(
            'access_token=mock_access_token&expires=3600&refresh_token=mock_refresh_token&otherKey={1234}'
        );
        $response->shouldReceive('getHeader')->andReturn(['content-type' => 'application/x-www-form-urlencoded']);
        $response->shouldReceive('getStatusCode')->andReturn(200);

        $client = m::mock('GuzzleHttp\ClientInterface');
        $client->shouldReceive('send')->times(1)->andReturn($response);

        $provider->setHttpClient($client);

        $token = $provider->getAccessToken('authorization_code', ['code' => 'mock_authorization_code']);
        $this->assertEquals(
            $provider->getBaseStravaUrl() . '/oauth/authorize',
            $provider->getBaseAuthorizationUrl()
        );
        $this->assertEquals(
            $provider->getBaseStravaUrl() . '/oauth/token',
            $provider->getBaseAccessTokenUrl([])
        );
        $this->assertEquals(
            $provider->getBaseStravaUrl() . '/api/v3/athlete',
            $provider->getResourceOwnerDetailsUrl($token)
        );
        $this->assertEquals(
            $provider->getApiVersion(),
            $this->apiVersion
        );

    }

    public function testUserData()
    {
        $userId = rand(1000, 9999);
        $firstName = uniqid();
        $lastName = uniqid();
        $premium = false;

        $postResponse = m::mock('Psr\Http\Message\ResponseInterface');
        $postResponse->shouldReceive('getBody')->andReturn(
            'access_token=mock_access_token&expires=3600&refresh_token=mock_refresh_token&otherKey={1234}'
        );
        $postResponse->shouldReceive('getHeader')->andReturn(['content-type' => 'application/x-www-form-urlencoded']);
        $postResponse->shouldReceive('getStatusCode')->andReturn(200);
        $userResponse = m::mock('Psr\Http\Message\ResponseInterface');
        $userResponse->shouldReceive('getBody')->andReturn(
            '{"id": ' . $userId . ', "firstname": "' . $firstName . '", "lastname": "' . $lastName . '", "premium": "' . $premium . '"}'
        );
        $userResponse->shouldReceive('getHeader')->andReturn(['content-type' => 'json']);
        $userResponse->shouldReceive('getStatusCode')->andReturn(200);

        $client = m::mock('GuzzleHttp\ClientInterface');
        $client->shouldReceive('send')
            ->times(2)
            ->andReturn($postResponse, $userResponse);

        $provider = $this->getProvider();

        $provider->setHttpClient($client);
        $token = $provider->getAccessToken('authorization_code', ['code' => 'mock_authorization_code']);
        $user = $provider->getResourceOwner($token);

        $this->assertEquals($userId, $user->getId());
        $this->assertEquals($userId, $user->toArray()['id']);
        $this->assertEquals($firstName, $user->getFirstName());
        $this->assertEquals($firstName, $user->toArray()['firstname']);
        $this->assertEquals($lastName, $user->getLastName());
        $this->assertEquals($lastName, $user->toArray()['lastname']);
        $this->assertEquals($premium, $user->getPremium());
        $this->assertEquals($premium, $user->toArray()['premium']);
    }

    public function testExceptionThrownWhenErrorObjectReceived()
    {
        $this->expectException(IdentityProviderException::class);

        $message = uniqid();
        $status = rand(400, 600);

        $postResponse = m::mock('Psr\Http\Message\ResponseInterface');
        $postResponse->shouldReceive('getBody')->andReturn(' {"message":"' . $message . '"}');
        $postResponse->shouldReceive('getHeader')->andReturn(['content-type' => 'json']);
        $postResponse->shouldReceive('getStatusCode')->andReturn($status);

        $client = m::mock('GuzzleHttp\ClientInterface');
        $client->shouldReceive('send')
            ->times(1)
            ->andReturn($postResponse);

        $provider = $this->getProvider();
        $provider->setHttpClient($client);
        $provider->getAccessToken('authorization_code', ['code' => 'mock_authorization_code']);
    }
}
