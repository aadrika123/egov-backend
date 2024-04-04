<?php

namespace App\Pipelines;

use Closure;

/**
 * | Created On-03-04-2023 
 * | Created By-Mrinal Kumar
 */
class SearchApplication
{
    public function handle($request, Closure $next)
    {
        $key = app('pipeline.key');
        if (!request()->has('holdingNo')) {
            return $next($request);
        }
        return $next($request)
            ->orderBy('id')
            ->where('holding_no', request()->input('holdingNo'))
            ->orWhere('new_holding_no', request()->input('holdingNo'));
    }
}
