<?php

namespace Webkul\Core\Http\Middleware;

use Illuminate\Http\Request;
use Webkul\Core\Repositories\LocaleRepository;
use Closure;

class SearchTrimMIddleware
{
    /**
     * @var LocaleRepository
     */
    protected $locale;

    /**
     * @param LocaleRepository $locale
     */
    public function __construct(LocaleRepository $locale)
    {
        $this->locale = $locale;
    }

    /**
    * Handle an incoming request.
    *
    * @param  \Illuminate\Http\Request  $request
    * @param  \Closure  $next
    * @return mixed
    */
    public function handle( Request $request, Closure $next)
    {
        $request->merge(array_map('trim', $request->all()));
        return $next($request);
    }
}
