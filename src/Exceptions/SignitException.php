<?php

namespace Signit\Exceptions;

class SignitException extends \RuntimeException
{
    private $httpStatus;
    private $responseBody;

    public function __construct($message, $httpStatus = 0, array $responseBody = [], \Throwable $previous = null)
    {
        parent::__construct($message, (int) $httpStatus, $previous);
        $this->httpStatus = (int) $httpStatus;
        $this->responseBody = $responseBody;
    }

    public function getHttpStatus() { return $this->httpStatus; }
    public function getResponseBody() { return $this->responseBody; }
}
