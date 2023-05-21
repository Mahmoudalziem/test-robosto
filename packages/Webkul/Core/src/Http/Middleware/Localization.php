<?php

namespace Webkul\Core\Http\Middleware;

use Webkul\Core\Repositories\LocaleRepository;
use Closure;

class Localization
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
    public function handle($request, Closure $next)
    {
        // Get Lang from Request
        $locale = request()->header('lang');

        // If locale Exist in URL Query and in DB, then set locale to it
        if ($locale && $this->locale->findOneByField('code', $locale)) {
            app()->setLocale($locale);
        } else {
            // Else, Set default locale from DB
            app()->setLocale($this->locale->findOneByField('default', '1')->code);
        }

        return $next($request);
    }
}
