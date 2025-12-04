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
        if (!empty($object->field)) {
            $field = $object->field;
            $error = $object->error;
        }

        return new self(
            "Error executing API call ({$field}: {$error})",
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
            throw new self("Unable to decode PayoCity Payment HUB response: '{$body}'.");
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
