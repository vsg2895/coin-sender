<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TeamsPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $user = auth()->user();
        $modelId = optional($request->route('project'))->id
            ?? optional(optional($request->route('ambassadorTask'))->task)->project_id;

        if (is_null($modelId)) {
            if ($user->hasRole('Catapult Manager')) {
                $modelId = 0;
            } else {
                $result = optional(DB::selectOne('SELECT project_id FROM project_members WHERE `project_members`.`userable_id` = ? AND `project_members`.`userable_type` = ? LIMIT 1', [$user->id, 'App\Models\Manager']));
                $modelId = $result->project_id;
            }
        }

        // 0 is temporary global? see discussion: https://github.com/spatie/laravel-permission/discussions/2088
        setPermissionsTeamId($modelId ?? 0);
        return $next($request);
    }
}
