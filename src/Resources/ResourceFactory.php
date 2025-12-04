<?php

namespace CodebrainPyc\Hub\Resources;

class ResourceFactory
{
    /**
     * Create resource object from Api result, filtered by properties that should exist.
     *
     * @param object $apiResult
     */
    public static function createFromApiResult($apiResult, BaseResource $resource)
    {
        // Filter by properties that should exist
        $resourceProperties = $resource->getAllowedProperties();

        // Check if the api result contains the required properties
        foreach ($resourceProperties as $property) {
            if (property_exists($apiResult, $property)) {
                $resource->{$property} = $apiResult->{$property};
            }
        }

        return $resource;
    }
}
