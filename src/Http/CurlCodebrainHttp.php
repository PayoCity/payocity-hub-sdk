<?php

namespace CodebrainPyc\Hub\Http;

use CodebrainPyc\Hub\CodebrainPycApiClient;
use CodebrainPyc\Hub\Exceptions\ApiException;
use CodebrainPyc\Hub\Exceptions\CurlException;
use Composer\CaBundle\CaBundle;

final class CurlCodebrainHttp implements CodebrainHttpInterface
{
    /**
     * Default response timeout (in seconds).
     */
    public const DEFAULT_TIMEOUT = 10;

    /**
     * Default connect timeout (in seconds).
     */
    public const DEFAULT_CONNECT_TIMEOUT = 2;

    /**
     * HTTP status code for an empty ok response.
     */
    public const HTTP_NO_CONTENT = 204;

    /**
     * The maximum number of retries.
     */
    public const MAX_RETRIES = 5;

    /**
     * The amount of milliseconds the delay is being increased with on each retry.
     */
    public const DELAY_INCREASE_MS = 1000;

    /**
     * @param string $httpMethod
     * @param string $url
     * @param array  $headers
     * @param string $httpBody
     *
     * @return \stdClass|void|null
     *
     * @throws \CodebrainPyc\Hub\Exceptions\ApiException
     * @throws \CodebrainPyc\Hub\Exceptions\CurlException
     */
    public function send($httpMethod, $url, $headers, $httpBody)
    {
        for ($i = 0; $i <= self::MAX_RETRIES; ++$i) {
            usleep($i * self::DELAY_INCREASE_MS);

            try {
                return $this->attemptRequest($httpMethod, $url, $headers, $httpBody);
            } catch (CurlException $e) {
                throw new CurlException($e->getMessage());
            }
        }

        throw new CurlException('Unable to connect to the Codebrain HUB. Maximum number of retries ('.self::MAX_RETRIES.') reached.');
    }

    /**
     * @param string $httpMethod
     * @param string $url
     * @param array  $headers
     * @param string $httpBody
     *
     * @return \stdClass|void|null
     *
     * @throws \CodebrainPyc\Hub\Exceptions\ApiException
     */
    private function attemptRequest($httpMethod, $url, $headers, $httpBody)
    {
        // Validate input parameters
        if (empty($httpMethod)) {
            throw new ApiException('HTTP method cannot be empty.');
        }

        if (empty($url) || !filter_var($url, FILTER_VALIDATE_URL)) {
            throw new ApiException('Invalid or empty URL provided.');
        }

        $curl = curl_init($url);
        if ($curl === false) {
            throw new CurlException('Failed to initialize cURL session.');
        }

        $headers['Content-Type'] = 'application/json';

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $this->parseHeaders($headers));
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, self::DEFAULT_CONNECT_TIMEOUT);
        curl_setopt($curl, CURLOPT_TIMEOUT, self::DEFAULT_TIMEOUT);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($curl, CURLOPT_HEADER, true);
        curl_setopt($curl, CURLOPT_CAINFO, CaBundle::getBundledCaBundlePath());

        switch ($httpMethod) {
            case CodebrainPycApiClient::HTTP_POST:
                curl_setopt($curl, CURLOPT_POST, true);
                curl_setopt($curl, CURLOPT_POSTFIELDS, $httpBody);

                break;
            case CodebrainPycApiClient::HTTP_GET:
                break;
            default:
                throw new \InvalidArgumentException('Invalid http method: '.$httpMethod);
        }

        $startTime = microtime(true);
        $response = curl_exec($curl);
        $endTime = microtime(true);

        if ($response === false) {
            $executionTime = $endTime - $startTime;
            $curlErrorNumber = curl_errno($curl);
            $curlErrorMessage = 'Curl error: '.curl_error($curl);

            if ($this->isConnectTimeoutError($curlErrorNumber, $executionTime)) {
                curl_close($curl);
                throw new CurlException(
                    sprintf(
                        'Unable to connect to the PayoCity Payment HUB. (Error %d: %s). Execution time: %.2fs',
                        $curlErrorNumber,
                        $curlErrorMessage,
                        $executionTime
                    )
                );
            }

            curl_close($curl);
            throw new ApiException(
                sprintf('Curl error %d: %s', $curlErrorNumber, $curlErrorMessage),
                $curlErrorNumber
            );
        }

        // extract header
        $headerSize = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
        $header = substr($response, 0, $headerSize);
        $headers = $this->getHeaders($header);

        // extract body
        $httpBody = substr($response, $headerSize);

        $statusCode = curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        curl_close($curl);

        return $this->parseResponseBody($response, $headers, $statusCode, $httpBody);
    }

    /**
     * The version number for the underlying http client, if available.
     *
     * @example Guzzle/7.7
     *
     * @return string|null
     */
    public function versionString()
    {
        return 'Curl/*';
    }

    /**
     * Whether this http adapter provides a debugging mode. If debugging mode is enabled, the
     * request will be included in the ApiException.
     *
     * @return false
     */
    public function supportsDebugging()
    {
        return false;
    }

    /**
     * @param int          $curlErrorNumber
     * @param string|float $executionTime
     *
     * @return bool
     */
    private function isConnectTimeoutError($curlErrorNumber, $executionTime)
    {
        $connectErrors = [
            \CURLE_COULDNT_RESOLVE_HOST => true,
            \CURLE_COULDNT_CONNECT => true,
            \CURLE_SSL_CONNECT_ERROR => true,
            \CURLE_GOT_NOTHING => true,
        ];

        if (isset($connectErrors[$curlErrorNumber])) {
            return true;
        }

        if ($curlErrorNumber === \CURLE_OPERATION_TIMEOUTED) {
            if ($executionTime > self::DEFAULT_TIMEOUT) {
                return false;
            }

            return true;
        }

        return false;
    }

    /**
     * @param string $response
     * @param array  $headers
     * @param int    $statusCode
     * @param string $httpBody
     *
     * @return \stdClass|null
     *
     * @throws \CodebrainPyc\Hub\Exceptions\ApiException
     */
    private function parseResponseBody($response, $headers, $statusCode, $httpBody)
    {
        if (empty($response)) {
            if ($statusCode === self::HTTP_NO_CONTENT) {
                return null;
            }
            
            throw new ApiException(
                sprintf('No response body found. HTTP Status: %d', $statusCode),
                $statusCode
            );
        }

        $object = new \stdClass();
        $object->body = @json_decode($httpBody);
        $object->headers = $headers;

        // Checks if the response is valid JSON
        if (json_last_error() !== JSON_ERROR_NONE) {
            $jsonError = json_last_error_msg();
            throw new ApiException(
                sprintf(
                    "Unable to decode PayoCity Payment HUB response: (JSON Error: %s). Status: %d. Response: '%s'",
                    $jsonError,
                    $statusCode,
                    mb_substr($httpBody, 0, 200)
                ),
                $statusCode
            );
        }

        // Checks if the response has an error
        if (isset($object->body->error)) {
            $errorDetails = is_string($object->body->error) 
                ? $object->body->error 
                : json_encode($object->body->error);
            throw new ApiException(
                sprintf("PayoCity Payment HUB error: (Status %d): %s", $statusCode, $errorDetails),
                $statusCode
            );
        }

        return $object;
    }

    private function parseHeaders($headers)
    {
        $result = [];

        foreach ($headers as $key => $value) {
            $result[] = $key.': '.$value;
        }

        return $result;
    }

    /**
     * Get the headers from the response.
     *
     * @param string $respHeaders
     *
     * @return array
     */
    private function getHeaders($respHeaders)
    {
        $headers = [];

        $headerText = substr($respHeaders, 0, strpos($respHeaders, "\r\n\r\n"));

        foreach (explode("\r\n", $headerText) as $i => $line) {
            if ($i === 0) {
                $headers['http_code'] = $line;
            } else {
                list($key, $value) = explode(': ', $line);

                $headers[strtolower($key)] = $value;
            }
        }

        return $headers;
    }
}
