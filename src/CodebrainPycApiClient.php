<?php

namespace CodebrainPyc\Hub;

use CodebrainPyc\Hub\Endpoints\AuthenticationEndpoint;
use CodebrainPyc\Hub\Endpoints\PaymentjobEndpoint;
use CodebrainPyc\Hub\Exceptions\ApiException;
use CodebrainPyc\Hub\Http\CodebrainHttpPicker;

class CodebrainPycApiClient
{
    /**
     * Version of our client.
     */
    public const CLIENT_VERSION = '1.0.2';

    /**
     * Version of the API.
     */
    public const API_VERSION = 'v2';

    /**
     * Poduction endpoint of the remote API.
     */
    public const API_ENDPOINT = 'https://api.payocityhub.com';

    /*
    * HTTP Methods
    */
    public const HTTP_GET = 'GET';
    public const HTTP_POST = 'POST';

    /**
     * API key for the API.
     *
     * @var string
     */
    protected $apiKey;

    /**
     * Secret key for the API.
     *
     * @var string
     */
    protected $apiSecret;

    /**
     * @var string
     */
    protected $apiEndpoint = self::API_ENDPOINT;

    /**
     * @var \CodebrainPyc\Hub\Http\CodebrainHttpInterface
     */
    protected $httpClient;

    /**
     * RESTful Payments resource.
     *
     * @var PaymentjobEndpoint
     */
    public $payment;

    public function __construct($httpClient = null, $httpAdapterPicker = null)
    {
        $httpAdapterPicker = $httpAdapterPicker ?: new CodebrainHttpPicker();
        $this->httpClient = $httpAdapterPicker->pickHttpAdapter($httpClient);

        $this->initializeEndpoints();
    }

    public function initializeEndpoints()
    {
        $this->payment = new PaymentjobEndpoint($this);
    }

    /**
     * @param string $apiKey the shop's API key
     *
     * @return CodebrainPycApiClient
     *
     * @throws \InvalidArgumentException
     */
    public function setApiKey($apiKey)
    {
        $apiKey = trim($apiKey);

        // API key has to be atleast 150 characters long.
        if (!preg_match('/^[a-zA-Z0-9#\/="]{150,}$/', $apiKey)) {
            throw new \InvalidArgumentException('API key cannot be empty, or has to be longer then 150 characters.');
        }

        $this->apiKey = $apiKey;

        return $this;
    }

    /**
     * @param string $apiSecret the shop's API key
     *
     * @return CodebrainPycApiClient
     *
     * @throws \InvalidArgumentException
     */
    public function setApiSecret($apiSecret)
    {
        $apiSecret = trim($apiSecret);

        // Check Accesstoken
        if (empty($apiSecret)) {
            throw new \InvalidArgumentException('Secret Key cannot be empty, please use the secret key displayed in the Codebrain HUB for this Point of Sale.');
        }

        $this->apiSecret = $apiSecret;

        return $this;
    }

    /**
     * Perform a http call. This method is used by the resource specific classes.
     *
     * @param string      $httpMethod
     * @param string      $apiPath
     * @param string|null $httpBody
     *
     * @return \stdClass
     *
     * @throws ApiException
     */
    public function doHttpCall($httpMethod, $apiPath, $httpBody = null)
    {
        $url = $this->apiEndpoint.'/'.self::API_VERSION.'/'.$apiPath;

        return $this->performHttpCall($httpMethod, $url, $httpBody);
    }

    /**
     * Perform a http call to a full url. This method is used by the resource specific classes.
     *
     * @see $payments
     * @see $isuers
     *
     * @param string      $httpMethod
     * @param string      $url
     * @param string|null $httpBody
     *
     * @return \stdClass|null
     *
     * @throws ApiException
     */
    public function performHttpCall($httpMethod, $url, $httpBody = null)
    {
        if (empty($this->apiKey)) {
            throw new ApiException('You have not set an API key. Please use setApiKey() to set the API key in the client.');
        }

        $headers = [
            'Accept' => 'application/json',
            'Authorization' => "Bearer {$this->apiKey}",
        ];

        if ($httpBody !== null) {
            $headers['Content-Type'] = 'application/json';
        }

        if (function_exists('php_uname')) {
            $headers['X-Codebrain-Client'] = php_uname();
        }

        $response = $this->httpClient->send($httpMethod, $url, $headers, $httpBody);

        // Check if x-signature header exists and the hash is correct
        if (!$response->hasHeader('x-signature')) {
            throw new ApiException('Missing signature header.');
        }

        $body = (string) $response->getBody();
        $signature = hash_hmac('sha512', $body, $this->apiSecret);
        $signatureHeader = $response->getHeaderLine('x-signature');

        if (!hash_equals($signature, $signatureHeader)) {
            throw new ApiException('Invalid signature.');
        }

        $decoded = @json_decode($body);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new ApiException("Unable to decode Codebrain HUB response: '{$body}'.");
        }

        return $decoded;
    }


    /**
     * Checks if the return is valid from the Codebrain HUB.
     *
     * @return bool
     *
     * @throws ApiException
     */
    public function validateReturn($data)
    {
        if (!isset($data['order_number']) || empty($data['order_number'])) {
            throw new ApiException('Missing Order Number.');
        }

        if (!isset($data['order_code']) || empty($data['order_code'])) {
            throw new ApiException('Missing Order Code.');
        }

        if (!isset($data['payment_job']) || empty($data['payment_job']) || !$this->isUuid($data['payment_job'])) {
            throw new ApiException('Missing or invalid Paymentjob ID.');
        }

        if (!isset($data['signature']) || empty($data['signature'])) {
            throw new ApiException('Missing signature string.');
        }

        $hashString = $data['order_number'].','.$data['order_code'].','.$data['payment_job'];

        $signature = hash_hmac('sha512', $hashString, $this->apiSecret);

        if (!hash_equals($signature, $data['signature'])) {
            throw new ApiException('Invalid signature.');
        }

        return true;
    }

    /**
     * Checks if the webhook is valid from the Codebrain HUB.
     *
     * @param string $jsonData
     *
     * @return bool
     *
     * @throws ApiException
     */
    public function validateWebhook($jsonData)
    {
        if (empty($jsonData)) {
            throw new ApiException('Missing JSON data.');
        }

        $headers = $this->getHeaders();

        // Header X-Signature is required
        if (!isset($headers['x-signature']) || empty($headers['x-signature'])) {
            throw new ApiException('Missing signature header.');
        }

        // Check signature
        $hash = hash_hmac('sha512', $jsonData, $this->apiSecret);

        $signature = $headers['x-signature'];

        if (!hash_equals($signature, $hash)) {
            throw new ApiException('Invalid signature.');
        }

        return true;
    }

    private function getHeaders()
    {
        $aHeaders = [];

        foreach ($_SERVER as $key => $value) {
            if (substr($key, 0, 5) == 'HTTP_') {
                $key = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($key, 5)))));

                if ($key == 'X-Signature' || $key == 'Accept-Language') {
                    $key = strtolower($key);
                }

                $aHeaders[$key] = $value;
            } else {
                $aHeaders[$key] = $value;
            }
        }

        return $aHeaders;
    }

    private function isUuid($string)
    {
        if (!is_string($string)) {
            return false;
        }

        return preg_match('/^[\da-fA-F]{8}-[\da-fA-F]{4}-[\da-fA-F]{4}-[\da-fA-F]{4}-[\da-fA-F]{12}$/D', $string) > 0;
    }
}
