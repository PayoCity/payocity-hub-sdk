<?php

namespace CodebrainPyc\Hub\Resources;

use CodebrainPyc\Hub\States\PaymentStates;

class Paymentjob extends BaseResource
{
    /**
     * Amount containing the value to be paid, currency is set to EUR by default.
     *
     * @var string
     */
    public $amountDue;

    /**
     * Amount containing the value that has been paid, currency is set to EUR by default.
     *
     * @var string
     */
    public $amountPaid = 0.00;

    /**
     * ISO 8601 date of the creation of the Paymentjob.
     *
     * @example "2023-08-22T09:23:59+02:00"
     *
     * @var string
     */
    public $created;

    /**
     * ISO 8601 date of the last update of the Paymentjob.
     *
     * @example "2023-08-22T09:23:59+02:00"
     *
     * @var string
     */
    public $lastUpdate;

    /**
     * The used currency for the payment, only "EUR" is supported.
     *
     * @var string
     */
    public $currency = 'EUR';

    /**
     * Error message of the payment.
     *
     * @var string|null
     */
    public $error;

    /**
     * Ip address of the customer that is paying.
     *
     * @var string
     */
    public $ipAddress;

    /**
     * Language of the payment pages.
     *
     * @var string
     */
    public $language;

    /**
     * Order ID of the payment.
     *
     * @var string
     */
    public $orderId;

    /**
     * Order Number of the payment.
     *
     * @var string
     */
    public $orderNumber;

    /**
     * Payment method of the payment.
     *
     * @var string
     */
    public $paymentMethod;

    /**
     * Payment steps of the payment.
     *
     * @var object
     */
    public $payments;

    /**
     * Paymentjob's ID of the payment.
     *
     * @var object
     */
    public $paymentJob;

    /**
     * Redirect URL to start the payment with.
     *
     * @var string
     */
    public $payUrl;

    /**
     * Return URL set on this payment.
     *
     * @var string
     */
    public $returnUrl;

    /**
     * Unix timestamp of the payment.
     *
     * @var int
     */
    public $timestamp;

    /**
     * Datetime timestamp of the payment.
     *
     * @example "2023-08-22T09:23:59+02:00"
     *
     * @var string
     */
    public $timestampDatetime;

    /**
     * Webhook URL set on this payment.
     *
     * @var string|null
     */
    public $webhookUrl;

    /**
     * The status of the payment.
     *
     * @var string
     */
    public $status = PaymentStates::STATUS_OPEN;

    /**
     * Allowed properties for this resource.
     *
     * @var array
     */
    protected static $allowedProperties = [
        'amountDue',
        'amountPaid',
        'created',
        'lastUpdate',
        'currency',
        'error',
        'language',
        'orderId',
        'orderNumber',
        'paymentMethod',
        'payments',
        'paymentJob',
        'payUrl',
        'status',
    ];

    /**
     * Is this payment canceled?
     *
     * @return bool
     */
    public function isCanceled()
    {
        return $this->status === PaymentStates::STATUS_CANCELED;
    }

    /**
     * Is this payment still open?
     *
     * @return bool
     */
    public function isOpen()
    {
        return $this->status === PaymentStates::STATUS_OPEN;
    }

    /**
     * Is this payment paid/settled?
     *
     * @return bool
     */
    public function isPaid()
    {
        return $this->status === PaymentStates::STATUS_PAID;
    }

    /**
     * Has the payment failed?
     *
     * @return bool
     */
    public function isFailed()
    {
        return $this->status === PaymentStates::STATUS_FAILED;
    }

    /**
     * Get the amount that is due for this paymentJob.
     *
     * @return float|null
     */
    public function getAmountDue()
    {
        if (empty($this->amountDue)) {
            return null;
        }

        return (float) $this->amountDue;
    }

    /**
     * Get the amount that has been paid for this paymentJob.
     *
     * @return float
     */
    public function getAmountPaid()
    {
        if (empty($this->amountPaid)) {
            return 0.00;
        }

        return (float) $this->amountPaid;
    }

    /**
     * Get the error(s) of the payment.
     *
     * @return string|null
     */
    public function getError()
    {
        if (empty($this->error)) {
            return null;
        }

        return $this->error;
    }

    /**
     * Get the paymentjob ID.
     *
     * @return string|null
     */
    public function getPaymentJobId()
    {
        if (empty($this->paymentJob)) {
            return null;
        }

        return $this->paymentJob;
    }

    /**
     * Get the order ID of the payment.
     *
     * @return string|null
     */
    public function getOrderId()
    {
        if (empty($this->orderId)) {
            return null;
        }
        
        return $this->orderId;
    }

    /**
     * Get the payment steps.
     *
     * @return array|null
     */
    public function getPaymentSteps()
    {
        if (empty($this->payments->steps)) {
            return null;
        }

        return $this->payments->steps;
    }

    /**
     * Get the redirect URL where the customer can complete the payment.
     *
     * @return string|null
     */
    public function getRedirectUrl()
    {
        if (empty($this->payUrl)) {
            return null;
        }

        return $this->payUrl;
    }

    /**
     * Get the paymentjob status.
     *
     * @return string|null
     */
    public function getStatus()
    {
        if (empty($this->status)) {
            return null;
        }

        return $this->status;
    }
}
