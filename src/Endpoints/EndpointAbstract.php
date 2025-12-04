<?php

namespace CodebrainPyc\Hub\Endpoints;

use CodebrainPyc\Hub\CodebrainPycApiClient;
use CodebrainPyc\Hub\Exceptions\ApiException;
use CodebrainPyc\Hub\Resources\BaseResource;
use CodebrainPyc\Hub\Resources\ResourceFactory;

abstract class EndpointAbstract
{
    public const REST_CREATE = CodebrainPycApiClient::HTTP_POST;
    public const REST_READ = CodebrainPycApiClient::HTTP_GET;

    /**
     * @var CodebrainPycApiClient
     */
    protected $client;

    /**
     * @var string
     */
    protected $resourcePath;

    /**
     * @var string|null
     */
    protected $parentId;

    public function __construct(CodebrainPycApiClient $api)
    {
        $this->client = $api;
    }

    /**
     * @return string
     */
    protected function buildQueryString(array $filters)
    {
        if (empty($filters)) {
            return '';
        }

        foreach ($filters as $key => $value) {
            if ($value === true) {
                $filters[$key] = 'true';
            }

            if ($value === false) {
                $filters[$key] = 'false';
            }
        }

        return '?'.http_build_query($filters, '', '&');
    }

    /**
     * @throws ApiException
     */
    protected function rest_create(array $body, array $filters)
    {
        $result = $this->client->doHttpCall(
            self::REST_CREATE,
            $this->getResourcePath().$this->buildQueryString($filters),
            $this->parseRequestBody($body)
        );

        return ResourceFactory::createFromApiResult($result, $this->getResourceObject());
    }

    /**
     * Retrieves a single object from the REST API using the GET method, for instance the status pull.
     *
     * @param string $id id of the object to retrieve
     *
     * @throws ApiException
     */
    protected function rest_read($id)
    {
        if (empty($id)) {
            throw new ApiException('Invalid resource id.');
        }

        $id = urlencode($id);
        $result = $this->client->doHttpCall(
            self::REST_READ,
            "{$this->getResourcePath()}/{$id}"
        );

        return ResourceFactory::createFromApiResult($result, $this->getResourceObject());
    }

    /**
     * Retrieves a single object from the REST API using the GET method, for instance the status pull.
     *
     * @throws ApiException
     */
    protected function rest_get(array $filters)
    {
        $result = $this->client->doAuthHttpCall(
            self::REST_READ,
            "{$this->getResourcePath()}".$this->buildQueryString($filters)
        );

        return ResourceFactory::createFromApiResult($result, $this->getResourceObject());
    }

    /**
     * @return string
     *
     * @throws ApiException
     */
    public function getResourcePath()
    {
        if (strpos($this->resourcePath, '_') !== false) {
            [$parentResource, $childResource] = explode('_', $this->resourcePath, 2);

            if (empty($this->parentId)) {
                throw new ApiException("Subresource '{$this->resourcePath}' used without parent '$parentResource' ID.");
            }

            return "$parentResource/{$this->parentId}/$childResource";
        }

        return $this->resourcePath;
    }

    /**
     * @return string|null
     */
    protected function parseRequestBody(array $body)
    {
        if (empty($body)) {
            return null;
        }

        return @json_encode($body);
    }

    /**
     * Get the object that is used by this API endpoint. Every API endpoint uses one type of object.
     *
     * @return BaseResource
     */
    abstract protected function getResourceObject();
}
