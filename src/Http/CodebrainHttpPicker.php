<?php

namespace CodebrainPyc\Hub\Http;

use CodebrainPyc\Hub\Exceptions\UnknownClientException;

class CodebrainHttpPicker implements CodebrainHttpPickerInterface
{
    /**
     * @param \GuzzleHttp\ClientInterface|\CodebrainPyc\Hub\Http\CodebrainHttpInterface|\stdClass|null $httpClient
     *
     * @return \CodebrainPyc\Hub\Http\CodebrainHttpInterface
     *
     * @throws \CodebrainPyc\Hub\Exceptions\UnknownClientException
     */
    public function pickHttpAdapter($httpClient)
    {
        if (!$httpClient) {
            if ($this->guzzleIsDetected()) {
                $guzzleVersion = $this->guzzleMajorVersionNumber();

                if ($guzzleVersion && in_array($guzzleVersion, [7])) {
                    return Guzzle7CodebrainHttp::createDefault();
                }
            }

            return new CurlCodebrainHttp();
        }

        if ($httpClient instanceof CodebrainHttpInterface) {
            return $httpClient;
        }

        if ($httpClient instanceof \GuzzleHttp\ClientInterface) {
            return new Guzzle7CodebrainHttp($httpClient);
        }

        throw new UnknownClientException('The provided http client or adapter was not recognized.');
    }

    /**
     * @return bool
     */
    private function guzzleIsDetected()
    {
        return interface_exists('\\'.\GuzzleHttp\ClientInterface::class);
    }

    /**
     * @return int|null
     */
    private function guzzleMajorVersionNumber()
    {
        // Guzzle 7
        if (defined('\GuzzleHttp\ClientInterface::MAJOR_VERSION')) {
            return (int) \GuzzleHttp\ClientInterface::MAJOR_VERSION;
        }

        return null;
    }
}
