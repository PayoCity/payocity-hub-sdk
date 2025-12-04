<?php

namespace CodebrainPyc\Hub\Http;

interface CodebrainHttpPickerInterface
{
    /**
     * @param \GuzzleHttp\ClientInterface|\CodebrainPyc\Hub\Http\CodebrainHttpPicker $httpClient
     *
     * @return \CodebrainPyc\Hub\Http\CodebrainHttpPicker
     */
    public function pickHttpAdapter($httpClient);
}
