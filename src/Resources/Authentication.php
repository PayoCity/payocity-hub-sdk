<?php

namespace CodebrainPyc\Hub\Resources;

class Authentication extends BaseResource
{
    /**
     * Accesstoken that is used to create a signature of the data.
     *
     * @var string
     */
    public $token;

    /**
     * Unix timestamp of the expiration of the accesstoken.
     *
     * @var int
     */
    public $expiration;

    /**
     * ISO 8601 date of the expiration of the accesstoken.
     *
     * @example "2023-08-22T09:23:59+02:00"
     *
     * @var string
     */
    public $expiration_datetime;

    /**
     * Allowed properties for this resource.
     *
     * @var array
     */
    protected static $allowedProperties = [
        'token',
        'expiration',
        'expiration_datetime',
    ];

    /**
     * Get the accesstoken.
     *
     * @return string|null
     */
    public function getAccessToken()
    {
        if (empty($this->token)) {
            return null;
        }

        return $this->token;
    }

    /**
     * Get the accesstoken expiration in unix timestamp.
     *
     * @return string|null
     */
    public function getExpiration()
    {
        if (empty($this->expiration)) {
            return null;
        }

        return $this->expiration;
    }

    /**
     * Get the accesstoken expiration in the ISO 8601 date format.
     *
     * @return string|null
     */
    public function getExpirationDatetime()
    {
        if (empty($this->expiration_datetime)) {
            return null;
        }

        return $this->expiration_datetime;
    }
}
