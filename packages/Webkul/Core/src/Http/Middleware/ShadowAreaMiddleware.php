<?php

namespace Webkul\Core\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Webkul\Area\Models\Area;

class ShadowAreaMiddleware
{
    /**
    * Handle an incoming request.
    *
    * @param  \Illuminate\Http\Request  $request
    * @param  \Closure  $next
    * @return mixed
    */
    public function handle(Request $request, Closure $next)
    {
        $areaID = request()->header('area');
        if ($areaID) {
            $areaID = Area::find($areaID);
            // if the given area has mainArea, then inject the main area in header
            if ($areaID && $areaID->mainArea) {
                $request->headers->set('area', $areaID->mainArea->id);        
            }

        }
        
        return $next($request);
    }
}
