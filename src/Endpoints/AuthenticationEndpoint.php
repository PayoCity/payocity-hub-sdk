<?php

namespace CodebrainPyc\Hub\Endpoints;

use CodebrainPyc\Hub\Exceptions\ApiException;
use CodebrainPyc\Hub\Resources\Authentication;

class AuthenticationEndpoint extends EndpointAbstract
{
    protected $resourcePath = 'guardian';

    /**
     * @return Authentication
     */
    protected function getResourceObject()
    {
        return new Authentication($this->client);
    }

    /**
     * Retreive an accesstoken from the Codebrain HUB.
     *
     * Will throw a ApiException if the shop cannot be found, or an accesstoken could not be generated.
     *
     * @return Authentication
     *
     * @throws ApiException
     */
    public function get(array $parameters = [])
    {
        return $this->rest_get($parameters);
    }
}
