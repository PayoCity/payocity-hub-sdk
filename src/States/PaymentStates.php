<?php

namespace CodebrainPyc\Hub\States;

class PaymentStates
{
    /**
     * Initial status of the payment, nothing has happened yet.
     */
    public const STATUS_OPEN = 'OPEN';

    /**
     * The customer has canceled the payment.
     */
    public const STATUS_CANCELED = 'CANCELLED';

    /**
     * The payment has been paid/completed.
     */
    public const STATUS_PAID = 'SUCCESS';

    /**
     * The payment has failed.
     */
    public const STATUS_FAILED = 'FAILURE';
}
