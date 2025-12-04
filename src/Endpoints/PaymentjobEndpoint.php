<?php

namespace CodebrainPyc\Hub\Endpoints;

use CodebrainPyc\Hub\Exceptions\ApiException;
use CodebrainPyc\Hub\Resources\Paymentjob;

class PaymentjobEndpoint extends EndpointAbstract
{
    // Payments
    protected $resourcePath = 'paymentjobs';

    /**
     * @return Paymentjob
     */
    protected function getResourceObject()
    {
        return new Paymentjob($this->client);
    }

    /**
     * Creates a paymentjob in the Codebrain HUB.
     *
     * @param array $data an array containing details on the payment
     *
     * @return Paymentjob
     *
     * @throws ApiException
     */
    public function create(array $data = [], array $filters = [])
    {
        return $this->rest_create($data, $filters);
    }

    /**
     * Retrieves a paymentjob from the Codebrain HUB.
     *
     * @param string $paymentjobId the UUID of the payment
     *
     * @return Paymentjob
     *
     * @throws ApiException
     */
    public function get(string $paymentjobId)
    {
        if (empty($paymentjobId)) {
            throw new ApiException("Missing paymentjob ID: '{$paymentjobId}'.");
        }

        return $this->rest_read($paymentjobId);
    }
}
