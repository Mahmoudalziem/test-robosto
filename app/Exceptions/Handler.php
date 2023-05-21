<?php

namespace App\Exceptions;

use Exception;
use Throwable;
use Predis\PredisException;
use Webkul\User\Models\Role;
use Webkul\User\Models\Admin;
use Illuminate\Queue\QueueManager;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Exception\GuzzleException;
use App\Exceptions\AreaNotCoveredException;
use Illuminate\Auth\AuthenticationException;
use Webkul\Core\Services\SendPushNotification;
use App\Exceptions\NotificationWithCurlException;
use App\Exceptions\PlaceOrderValidationException;
use Flugg\Responder\Exceptions\ConvertsExceptions;
use GuzzleHttp\Exception\InvalidArgumentException;
use Illuminate\Auth\Access\AuthorizationException;
use Webkul\Customer\Services\SMS\VictoryLink\SendSMS;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class Handler extends ExceptionHandler
{

    use ConvertsExceptions;
    /**
     * A list of the exception types that are not reported.
     *
     * @var array
     */
    protected $dontReport = [
        //
    ];
    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array
     */
    protected $dontFlash = [
        'password',
        'password_confirmation',
    ];

    /**
     * Report or log an exception.
     *
     * @param \Throwable $exception
     * @return void
     *
     * @throws Exception
     * @throws Throwable
     */
    public function report(Throwable $exception)
    {
        parent::report($exception);
    }

    public function render($request, Throwable $exception)
    {
        if ($exception instanceof HttpApiValidationException) {
            Log::info($request->url());
            Log::info($request->all());
            Log::info($exception->data());
            return $this->formatResponse($exception->getStatusCode(), $exception->getMessage(), $exception->data());
        }
        if ($exception instanceof AuthorizationException) {
            $code = $exception->getCode() ? $exception->getStatusCode() : $exception->status;
            return $this->formatResponse($code, $exception->getMessage(), null);
        }
        if ($exception instanceof AuthenticationException) {
            return $this->formatResponse(401, $exception->getMessage(), null);
        }
        if ($exception instanceof ModelNotFoundException) {
            return $this->formatResponse(404, 'Model Not Found', null);
        }
        if ($exception instanceof NotFoundHttpException) {
            return $this->formatResponse($exception->getStatusCode(), "This Route doesn't Exist", null);
        }

        if ($exception instanceof PromotionValidationException) {
            return $this->formatResponse($exception->getStatusCode(), $exception->getMessage(), null);
        }

        if ($exception instanceof ResponseErrorException) {
            return $this->formatResponse($exception->statusCode(), $exception->getMessage(), $exception->data());
        }

        if ($exception instanceof NotificationWithCurlException) {
            $msg = "في مشكلة حصلت وانا ببعت نوتيفيكشن باستخدام الكرل";
            $this->reportExceptionToAdmin($msg);
        }

        if ($exception instanceof PlaceOrderValidationException) {
            return $this->formatResponse($exception->getStatusCode(), $exception->getMessage(), $exception->data());
        }

        if ($exception instanceof AreaNotCoveredException) {
            return $this->formatResponse($exception->getStatusCode(), $exception->getMessage(), null);
        }

        if ($exception instanceof GuzzleException) {
            Log::info("Guzzle Exception");
            $this->reportExceptionToAdmin();
        }

        if ($exception instanceof InvalidArgumentException) {
            Log::info("Guzzle Exception");
            $this->reportExceptionToAdmin();
        }


        if ($exception instanceof PredisException) {
            Log::info("before");
            Log::info(config('cache.stores.redis.connection'));

            // Change Queue Redis Info
            config(['queue.connections.redis.connection' => 'redisFailOver']);

            // Change Cache Redis Info
            config(['cache.stores.redis.connection' => 'redisFailOver']);

            Log::info("after");
            Log::info(config('cache.stores.redis.connection'));

            $this->reportExceptionToAdmin();

            // $queue = new QueueManager(app());
            // $queue->setDefaultDriver('redis_failover');
            // Log::info('First Driver');
            // Log::info($queue->getDefaultDriver());
            // Log::info('Seconds Driver');
            // Log::info($queue->getDefaultDriver());
        }

        if ($exception instanceof \PhpAmqpLib\Exception\AMQPExceptionInterface) {
            Log::info('Connection Refused');
            Log::info("before");
            Log::info(config('queue.default'));
            // Change Queue Rabbitmq Info
            config(['queue.default' => 'rabbitmq_failover']);
            Log::info("after");
            Log::info(config('queue.default'));

            $this->reportExceptionToAdmin();
        }

        return parent::render($request, $exception);
    }

    //format exception response to responder
    protected function formatResponse($code, $message, $errors = null)
    {
        return response()->json([
            'data' => $errors,
            'status' => $code,
            'success' => false,
            'message' => $message,
        ], $code);
    }

    private function reportExceptionToAdmin($msg = null)
    {
        sendSMSToDevTeam($msg);
    }

    /**
     * Handle the event.
     *
     * @return bool
     * @throws InvalidOptionsException
     */
    // private function sendNotificationToAdmin() {
    //     $tokens = [];
    //     $admins = Admin::whereHas('roles', function ($q) {
    //                 $q->where('roles.slug', Role::SUPER_ADMIN);
    //             })->get();
    //     foreach ($admins as $admin) {
    //         $tokens = array_merge($tokens, $admin->deviceToken->pluck('token')->toArray());
    //     }

    //     $data = [
    //         'title' => "مشكلة في سيرفر الريديس",
    //         'body' => "لقد حدثت مشكلة في سيرفر الريديس",
    //     ];

    //     return SendPushNotification::send($tokens, $data);
    // }

}
