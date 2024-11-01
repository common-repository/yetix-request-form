<?php
namespace whodunit\client\zendesk;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Utils;
use http\Exception\InvalidArgumentException;
use Zendesk\API\Exceptions\ApiResponseException;
use Zendesk\API\Utilities\OAuth;

class ExtentedOAuth extends OAuth{
    /**
     * Requests for an access token.
     *
     * @param Client $client
     * @param string $subdomain
     * @param array  $params
     *
     * @param string $domain
     * @return array
     * @throws ApiResponseException
     */
    public static function getAccessToken( Client $client, $subdomain, array $params, $domain = 'zendesk.com')
    {
		self::validateSubdomain($subdomain);
        $authUrl  = "https://$subdomain.$domain/oauth/tokens";

        // Fetch access_token
        $params = array_merge( [
            'code'          => null,
            'client_id'     => null,
            'client_secret' => null,
            'grant_type'    => 'authorization_code',
            'scope'         => 'read write',
            'redirect_uri'  => null,
        ], $params);
        try {
            $request = new Request('POST', $authUrl, ['Content-Type' => 'application/json']);
            $request = $request->withBody( Utils::streamFor( json_encode( $params ) ) );
            $response = $client->send( $request );
        } catch (RequestException $e) {
            throw new ApiResponseException($e);
        }
        return json_decode($response->getBody()->getContents());
    }

	/**
	 * Validate subdomain
	 *
	 * @param string $subdomain
	 * @throws InvalidArgumentException
	 */
	protected static function validateSubdomain($subdomain)
	{
		if (! preg_match('/^[A-Za-z0-9](?:[A-Za-z0-9\-]{0,61}[A-Za-z0-9])?$/', $subdomain)) {
			throw new InvalidArgumentException('Invalid Zendesk subdomain.');
		}
	}
}
