<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Support\Facades\Response;
class Cors
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        header("Access-Control-Allow-Origin: *");
        // ALLOW OPTIONS METHOD
        $headers = [
            'Access-Control-Allow-Methods'=> 'POST, GET, OPTIONS, PUT, DELETE',
            'Access-Control-Allow-Headers'=> 'Content-Type, Accept, X-Auth-Token, Origin, Application, lang, area, status'
        ];
        if($request->getMethod() == "OPTIONS" ) {
            // The client-side application can set only headers allowed in Access-Control-Allow-Headers
            return Response::make('OPTIONS Ok', 200, $headers);
        }
        $response = $next($request);
        foreach($headers as $key => $value)
            $response->headers->set($key, $value);
        return $response;
    }
}