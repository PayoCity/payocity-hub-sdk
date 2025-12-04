<?php

namespace CodebrainPyc\Hub\Resources;

use CodebrainPyc\Hub\CodebrainPycApiClient;

abstract class BaseResource
{
    /**
     * @var CodebrainPycApiClient
     */
    protected $client;

    /**
     * Indicates the type of resource.
     *
     * @example payment
     *
     * @var string
     */
    protected $resource;

    /**
     * Allowed properties for this resource.
     *
     * @var array
     */
    protected static $allowedProperties = [];

    public function __construct(CodebrainPycApiClient $client)
    {
        $this->client = $client;
    }

    public function __debugInfo()
    {
        // Only show the allowed properties
        $properties = [];

        foreach (static::$allowedProperties as $property) {
            $properties[$property] = $this->$property;
        }

        return $properties;
    }

    /**
     * Get the allowed properties for this resource.
     *
     * @return array
     */
    public static function getAllowedProperties()
    {
        return static::$allowedProperties;
    }
}
