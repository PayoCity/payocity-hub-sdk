<?php

namespace CodebrainPyc\Hub\Http;

use CodebrainPyc\Hub\Exceptions\ApiException;
use Composer\CaBundle\CaBundle;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\RequestOptions as GuzzleRequestOptions;
use Psr\Http\Message\ResponseInterface;

final class Guzzle7CodebrainHttp implements CodebrainHttpInterface
{
    /**
     * Default read timeout (in seconds), default guzzle timeout is 10 seconds.
     */
    public const READ_TIMEOUT = 10;

    /**
     * Default connect timeout (in seconds), default guzzle timeout is 0 seconds (indefinitely).
     */
    public const CONNECT_TIMEOUT = 2;

    /**
     * HTTP status code for an empty ok response.
     */
    public const HTTP_NO_CONTENT = 204;

    /**
     * @var \GuzzleHttp\ClientInterface
     */
    private $httpClient;

    /**
     * Whether debugging is enabled. Enable debugging to allow the request to be added in the ApiException.
     * Otherwise its disabled by default.
     *
     * @var bool
     */
    private $debugging = false;

    public function __construct(ClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    /**
     * Create a new Guzzle 7 client with our configuration defaults.
     *
     * @return static
     */
    public static function createDefault()
    {
        $client = new Client([
            GuzzleRequestOptions::VERIFY => CaBundle::getBundledCaBundlePath(),
            GuzzleRequestOptions::TIMEOUT => self::READ_TIMEOUT,
            GuzzleRequestOptions::CONNECT_TIMEOUT => self::CONNECT_TIMEOUT,
        ]);

        return new Guzzle7CodebrainHttp($client);
    }

    /**
     * Send a request to the specified api url.
     *
     * @param string $httpMethod
     * @param string $url
     * @param array  $headers
     * @param string $httpBody
     *
     * @return \stdClass|null
     *
     * @throws \CodebrainPyc\Hub\Exceptions\ApiException
     */
    public function send($httpMethod, $url, $headers, $httpBody)
    {
        $request = new Request($httpMethod, $url, $headers, $httpBody);

        try {
            $response = $this->httpClient->send($request, ['http_errors' => false]);
        } catch (GuzzleException $e) {
            // Only include the request in the ApiException if debugging is enabled.
            if (!$this->debugging) {
                $request = null;
            }

            throw new ApiException($e->getMessage(), $e->getCode(), null, $request, null);
        }

        return $this->parseResponseBody($response);
    }

    /**
     * Whether debugging is possible. Enable debugging to allow the request to be added in the ApiException.
     * Otherwise its disabled by default.
     *
     * @return true
     */
    public function supportsDebugging()
    {
        return true;
    }

    /**
     * Whether debugging is enabled. Enable debugging to allow the request to be added in the ApiException.
     * Otherwise its disabled by default.
     *
     * @return bool
     */
    public function debugging()
    {
        return $this->debugging;
    }

    /**
     * Enable debugging for the client. Enable debugging to allow the request to be added in the ApiException.
     * Otherwise its disabled by default.
     */
    public function enableDebugging()
    {
        $this->debugging = true;
    }

    /**
     * Disable debugging for the client. Disable debugging prevents the request from being added to the ApiException. But its disabled by default.
     */
    public function disableDebugging()
    {
        $this->debugging = false;
    }

    /**
     * Parse the PSR-7 Response body.
     *
     * @return \stdClass|null
     *
     * @throws ApiException
     */
    private function parseResponseBody(ResponseInterface $response)
    {
        $body = (string) $response->getBody();
        if (empty($body)) {
            if ($response->getStatusCode() === self::HTTP_NO_CONTENT) {
                return null;
            }

            throw new ApiException('No response body found.');
        }

        $object = new \stdClass();

        // Add headers to body object
        $object->headers = $response->getHeaders();

        $object->body = @json_decode($body);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new ApiException("Unable to decode PayoCity Payment HUB response: '{$body}'.");
        }

        if ($response->getStatusCode() >= 400) {
            throw ApiException::createFromResponse($response, null);
        }

        return $object;
    }

    /**
     * The version number for the Guzzle http client, if available. This can be used for debugging purposes.
     *
     * @example Guzzle/7.7
     *
     * @return string|null
     */
    public function versionString()
    {
        if (defined('\GuzzleHttp\ClientInterface::MAJOR_VERSION')) { // Guzzle 7
            return 'Guzzle/'.ClientInterface::MAJOR_VERSION;
        }

        return null;
    }
}
