<?php

namespace CodebrainPyc\Hub\Exceptions;

class ApiException extends \Exception
{
    /**
     * @var string
     */
    protected $field;

    /**
     * @var string
     */
    protected $plainMessage;

    /**
     * @var \Psr\Http\Message\RequestInterface|null
     */
    protected $request;

    /**
     * @var \Psr\Http\Message\ResponseInterface|null
     */
    protected $response;

    /**
     * ISO8601 representation of the moment this exception was thrown.
     *
     * @var \DateTimeImmutable
     */
    protected $raisedAt;

    /**
     * @param string                                   $message
     * @param int                                      $code
     * @param string|null                              $field
     * @param \Psr\Http\Message\RequestInterface|null  $request
     * @param \Psr\Http\Message\ResponseInterface|null $response
     * @param \Throwable|null                          $previous
     *
     * @throws \CodebrainPyc\Hub\Exceptions\ApiException
     */
    public function __construct(
        $message = '',
        $code = 0,
        $field = null,
        $error = null,
        $request = null,
        $response = null,
        $previous = null
    ) {
        $this->plainMessage = $message;

        $this->raisedAt = new \DateTimeImmutable();

        $formattedRaisedAt = $this->raisedAt->format(\DateTime::ISO8601_EXPANDED);
        $message = "[{$formattedRaisedAt}] ".$message;

        if (!empty($field)) {
            $this->field = (string) $field;
            $message .= ". Field: {$this->field}";
        }

        if (!empty($response)) {
            $this->response = $response;
        }

        $this->request = $request;
        if ($request) {
            $requestBody = $request->getBody()->__toString();

            if ($requestBody) {
                $message .= ". Request body: {$requestBody}";
            }
        }

        parent::__construct($message, $code, $previous);
    }

    /**
     * @param \Psr\Http\Message\ResponseInterface $response
     * @param \Psr\Http\Message\RequestInterface  $request
     * @param \Throwable|null                     $previous
     *
     * @return \CodebrainPyc\Hub\Exceptions\ApiException
     *
     * @throws \CodebrainPyc\Hub\Exceptions\ApiException
     */
    public static function createFromResponse($response, $request = null, $previous = null)
    {
        $object = static::parseResponseBody($response);

        $field = null;
        $error = null;
        $message = 'Error executing API call';

        // Try to extract error information from various possible response formats
        if (!empty($object->field) && !empty($object->error)) {
            $field = $object->field;
            $error = $object->error;
            $message = "Error executing API call ({$field}: {$error})";
        } elseif (!empty($object->error)) {
            // API returned just an error field (no field name)
            $error = is_string($object->error) ? $object->error : json_encode($object->error);
            $message = "Error executing API call: {$error}";
        } elseif (!empty($object->message)) {
            // Some APIs use 'message' instead of 'error'
            $error = $object->message;
            $message = "Error executing API call: {$error}";
        } elseif (!empty($object->detail)) {
            // Some APIs use 'detail'
            $error = $object->detail;
            $message = "Error executing API call: {$error}";
        } else {
            // Generic error with status code
            $statusCode = $response->getStatusCode();
            $body = (string) $response->getBody();
            $truncatedBody = mb_substr($body, 0, 200);
            $message = "Error executing API call (HTTP {$statusCode}). Response: {$truncatedBody}";
        }

        return new self(
            $message,
            $response->getStatusCode(),
            $field,
            $error,
            $request,
            $response,
            $previous
        );
    }

    /**
     * @return string|null
     */
    public function getField()
    {
        return $this->field;
    }

    /**
     * @return \Psr\Http\Message\ResponseInterface|null
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * @return bool
     */
    public function hasResponse()
    {
        return $this->response !== null;
    }

    /**
     * @return \Psr\Http\Message\RequestInterface
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * Get the ISO8601 representation of the moment this exception was thrown.
     *
     * @return \DateTimeImmutable
     */
    public function getRaisedAt()
    {
        return $this->raisedAt;
    }

    /**
     * @param \Psr\Http\Message\ResponseInterface $response
     *
     * @return \stdClass
     *
     * @throws \CodebrainPyc\Hub\Exceptions\ApiException
     */
    protected static function parseResponseBody($response)
    {
        $body = (string) $response->getBody();

        $object = @json_decode($body);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $statusCode = $response->getStatusCode();
            $truncatedBody = mb_substr($body, 0, 500);
            throw new self(
                "Unable to decode PayoCity Payment HUB response:  (HTTP {$statusCode}, JSON Error: " . json_last_error_msg() . "). Response: '{$truncatedBody}'."
            );
        }

        return $object;
    }

    /**
     * Retrieve the plain exception message.
     *
     * @return string
     */
    public function getPlainMessage()
    {
        return $this->plainMessage;
    }
}
