<?php

namespace Webkul\Core\Http\Controllers;

use Carbon\Carbon;
use Firebase\JWT\JWT;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class BackendBaseController extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    /**
     * @param bool $error
     * @param int $responseCode
     * @param array $message
     * @param null $data
     * @return \Illuminate\Http\JsonResponse
     */
    protected function responseJson($error = true, $responseCode = 200, $message = null, $data = null)
    {
        return response()->json([
            'error'         =>  $error,
            'response_code' => $responseCode,
            'message'       => $message,
            'data'          =>  $data
        ]);
    }

    protected function responsePaginatedSuccess($data ,$message = null, $request)
    {
        if($data->resource instanceof LengthAwarePaginator){
            $data = $data->toResponse($request)->getData();
        }

        $data->status = 200;
        $data->success = true;
        $data->message = $message;

        return response()->json($data);
    }

    protected function responseSuccess($data = null, $message = null)
    {
        // Initalize Object
        $response = (object)[];

        $response->data     = $data;
        $response->status   = 200;
        $response->success  = true;

        if (isset($message)) {
            $response->message  = $message;
        }

        return response()->json($response);
    }


    protected function responseError($responseCode = 422, $message = null, $data = null)
    {
        if($data){
            $response['data']=$data;
        }

        $response['status']=$responseCode;
        $response['success']=false;

        if($message){
            $response['message']=$message;
        }


        return response()->json($response, $responseCode);
    }

    /**
     * @param string $reference
     * @param mixed $value
     * 
     * @return bool
     */
    public function triggerFCMDB(string $reference, $value)
    {
        $database = app('firebase.database');

        $reference =  $database->getReference($reference);

        $reference->set($value);

        return true;
    }
}
