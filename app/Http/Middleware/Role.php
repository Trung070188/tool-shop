<?php

namespace App\Http\Middleware;

use App\Exceptions\PermissionException;
use App\Models\Permission;
use App\Models\User;
use App\Models\UserRole;
use Closure;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\UnauthorizedException;

class Role extends Middleware
{
    public function handle($request, Closure $next)
    {
        $userId = auth()->user()->id;
        $action = $request->route('action');
        $class = Route::current()->getAction('controller');
        $role = ['cms_cps', 'admin'];

        if (!$action) {
            throw new \Exception("Route define must have {action}");
        }

        if (!$class) {
            throw new \Exception("Route define must have a class");
        }

        $p = UserRole::query()->join('roles', 'roles.id', '=', 'role_id')
            ->select(DB::raw('user_id ,ANY_VALUE(roles.type) type'))
            ->groupBy('user_id')->having('user_id', '=', $userId)->first();
        if (in_array($p->type, $role)) {
            return $next($request);
        } else {
            auth()->logout();
            return redirect()->route('login');
        }
    }

    /**
     * Get the path the user should be redirected to when they are not authenticated.
     *
     * @param \Illuminate\Http\Request $request
     * @return string|null
     */
    protected function redirectTo($request)
    {
        return route('login');
    }

    /**
     * Convert allowed route to regex patterns
     * @param array $allowed
     * @return string
     */
    private static function getPatterns(array $allowed)
    {
        $p = '/' . implode('|', array_map(function ($a) {
                $a = preg_quote($a, '/');
                $a = str_replace('*', '.*', $a);
                return $a;
            }, $allowed)) . '/';

        return $p;
    }
}
