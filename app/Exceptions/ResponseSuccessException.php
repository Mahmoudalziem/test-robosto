<?php

namespace App\Exceptions;

use Flugg\Responder\Exceptions\Http\HttpException;

class ResponseSuccessException extends HttpException
{
    protected $code = 200;
    protected $message;
    protected $data;
    protected $status = 200;
    public function __construct(?string $code = null, ?string $message = null, ?array $responderData = null, ?array $headers = null)
    {
        parent::__construct($message, $headers);
        $this->code = $code;
        $this->message = $message;
        $this->data = $responderData;
    }

    /**
     * Render the exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function render($request)
    {
        return response()->json([
            'data' => $this->data,
            'status' => $this->code,
            'success' => true,
            'message' => $this->message,
        ], $this->code);
    }
}
