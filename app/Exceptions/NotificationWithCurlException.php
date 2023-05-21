<?php

namespace App\Exceptions;

use Exception;

class NotificationWithCurlException extends Exception
{
    protected $status;
    protected $message;
    protected $error;
    protected $data;


    /**
     * @param mixed $status
     * @param null $message
     * @param null $error
     * @param null $data
     * 
     * @return HttpJsonResponse
     */
    public function __construct($status, $message = null, $error = null, $data = null, $headers = null)
    {
        parent::__construct($message, $headers);
        $this->status = $status;
        $this->message = $message;
        $this->error = $error;
        $this->data = $data;
    }
}

