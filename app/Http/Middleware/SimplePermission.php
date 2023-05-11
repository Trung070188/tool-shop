<?php

namespace App\Http\Middleware;

use App\Services\AppPermission;
use Closure;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;
use Illuminate\Support\Facades\Route;

class SimplePermission extends Middleware
{

    public function handle($request, Closure $next)
    {
        $action = $request->route('action');
        $class = Route::current()->getAction('controller');

        $appPermission = new AppPermission();

        if (!$appPermission->validate($class, $action, $request)) {
            $isAjax = $request->get('_ajax') == 1;
            if ($isAjax) {
                return response([
                    'code' => 401,
                    'message' => 'Bạn không có quyền vào link này',
                    'data' => [
                        'debug' => $appPermission->getDebugInfo(),
                        'required' => $appPermission->getRequiredPermissions(),
                    ]
                ]);
            }
            $title = 'Bạn không có quyền vào link này';
            $component = 'PermissionErrorPage';
            $jsonData = [
                'title' => $title,
                'permissionDebug' => $appPermission->getDebugInfo(),
                'permissionRequired' => $appPermission->getRequiredPermissions(),
            ];

            return response(vue(compact('title', 'component'), $jsonData));


        }


        return $next($request);
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
