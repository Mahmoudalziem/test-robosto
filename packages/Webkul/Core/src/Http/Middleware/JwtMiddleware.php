<?php

namespace Webkul\Core\Http\Middleware;

use Closure;
use Exception;
use Firebase\JWT\JWT;
use Firebase\JWT\ExpiredException;
use Illuminate\Auth\AuthenticationException;

class JwtMiddleware
{

    /**
    * Handle an incoming request.
    *
    * @param  \Illuminate\Http\Request  $request
    * @param  \Closure  $next
    * @return mixed
    */
    public function handle($request, Closure $next, $guard = null)
    {
        $token = $request->bearerToken();

        if(!$token) {
            // Unauthorized response if token not there
            throw new AuthenticationException();
        }
        
        $credentials = JWT::decode($token, config('robosto.JWT_SECRET'), ['HS256']);
        dd($credentials);

        try {
            
            $credentials = JWT::decode($token, config('robosto.JWT_SECRET'), ['HS256']);
        } catch(ExpiredException $e) {
            return response()->json(['error' => 'Provided token is expired.'], 400);

        } catch(Exception $e) {
            return response()->json(['error' => 'An error while decoding token.'], 400);

        }

        $user = User::find($credentials->sub);

        // Now let's put the user in the request class so that you can grab it from there
        $request->auth = $user;

        return $next($request);
    }
}
