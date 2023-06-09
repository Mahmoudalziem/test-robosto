<?php

namespace App\Exceptions;

use Flugg\Responder\Exceptions\Http\HttpException;

class ResponseErrorException extends HttpException
{
    protected $errorCode;
    protected $message;
    protected $data;
    protected $status = 406;
    public function __construct(?string $errorCode = null, ?string $message = null, ?array $responderData = null, ?array $headers = null)
    {
        parent::__construct($message, $headers);
        $this->errorCode = $errorCode;
        $this->message = $message;
        $this->data = $responderData;
    }
    public function data()
    {
        return $this->data;
    }

    public function statusCode(): int
    {
        return $this->errorCode;
    }
}
