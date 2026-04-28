<p align="center">
  <img src="https://docs.payocity.nl/assets/sdk.png" width="128" height="128"/>
</p>

<h1 align="center">PayoCity Payment HUB API client for PHP</h1>

## Requirements ##
To use the HUB API client, the following things are required:

+ Get yourself a free [HUB account](https://payocityhub.com/register). No sign up costs.
+ Create a POS and configure it for the desired PSP or Simulator to enable payments.
+ Now you're ready to use the HUB API client.

+ PHP >= 8.2
+ Up-to-date OpenSSL library

## Composer Installation ##

By far the easiest way to install the HUB API client is to require it with [Composer](http://getcomposer.org/doc/00-intro.md).



    $ composer require codebrain-pyc/hub

    {
        "require": {
            "codebrain-pyc/hub": "1.*"
        }
    }


## PaymentJob ##


The paymentJobs are central when it comes to the transactions that are performed by the HUB.
They contain all information about the transaction, like amounts, urls and steps taken.

Because of this, there are a lot of different functions available to interact with the paymentJob.
All of them are documented on: [PaymentJobs](https://docs.payocity.nl/paymentjobs)