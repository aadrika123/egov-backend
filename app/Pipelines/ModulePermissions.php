<?php

namespace App\Pipelines;

use Closure;

/**
 * | Created On-06-03-2023 
 * | Created By-Anshu Kumar
 * | Created for the Property Module Permissions by Role
 */
class ModulePermissions
{
    public function handle($request, Closure $next)
    {
        if (!request()->has('module')) {
            return $next($request);
        }

        return $next($request)
            ->join('wf_permissions as p', 'p.id', '=', 'wf_role_permissions.permision_id')
            ->where('p.module_id', request()->input('module'));
    }
}
