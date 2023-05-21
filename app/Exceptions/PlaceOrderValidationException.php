<?php

namespace App\Exceptions;
use Flugg\Responder\Exceptions\Http\HttpException;
class PlaceOrderValidationException extends HttpException
{
    protected $errorCode;
    protected $message;
    protected $data;
    protected $status = 422;
    public function __construct(?string $errorCode=null,?string $message = null,?array $responderData=null,?array $headers = null )
    {
        parent::__construct($message, $headers);
        $this->errorCode=$errorCode;
        $this->message=$message;
        $this->data=$responderData;
    }

    public function getStatusCode(){
        return $this->errorCode;
    }
}
