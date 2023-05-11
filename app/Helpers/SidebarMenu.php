<?php

namespace App\Helpers;

use App\Models\Group;
use App\Models\User;
use App\Models\UserGroup;
use Illuminate\Support\Facades\Cache;

class SidebarMenu
{
    private static $menus = [];

    public static function addMenu($menu)
    {
        self::$menus = array_merge(self::$menus, $menu);
    }

    public static function cleanCache($id)
    {
        $rKey = 'menucache.' . $id;
        Cache::forget($rKey);
    }

    public static function getMenus()
    {
        /**
         * @var User $user
         */
        $user = auth()->user();
        $env = config('app.env');
        $rKey = '@menucache.' . $user->id;

        if ($env === 'production') {
            $data = Cache::get($rKey);

            if ($data) {
                return unserialize($data);
            }
        }

        $groupMap = UserGroup::where('user_id', $user->id)->get()->pluck('id', 'group_id');

        self::reflectMenu();

        $menus = array_merge(config('menu'), self::$menus);

        if ($user->hasGroup(Group::SUPER_USER)) {
            usort($menus, function ($a, $b) {
                $a['order'] = $a['order'] ?? 0;
                $b['order'] = $b['order'] ?? 0;

                return $b['order'] - $a['order'];
            });
            Cache::put($rKey, serialize($menus), 300);

            return $menus;
        }
        $newMenus = [];

        foreach ($menus as $i => $menu) {
            if (empty($menu['group'])) {
                $menu['group'] = Group::SUPER_USER;
            }

            if (!empty($menu['children'])) {
                $newSubMenu = [];

                foreach ($menu['children'] as $submenu) {
                    if (empty($submenu['group'])) {
                        $submenu['group'] = Group::SUPER_USER;
                    }

                    if (isset($groupMap[$submenu['group']])) {
                        $newSubMenu[] = $submenu;
                    }
                }

                $menu['children'] = $newSubMenu;
            }

            if (isset($groupMap[$menu['group']])) {
                $newMenus[] = $menu;
            }
        }

        usort($newMenus, function ($a, $b) {
            $a['order'] = $a['order'] ?? 0;
            $b['order'] = $b['order'] ?? 0;

            return $b['order'] - $a['order'];
        });

        if (count($newMenus) === 1) {
            $newMenus = $newMenus[0]['children'];
        }
        Cache::put($rKey, serialize($newMenus), 300);

        return $newMenus;
    }

    private static function reflectMenu()
    {
        $dir = app_path('Http/Controllers/Admin');
        $files = scandir($dir);
        $controllers = [];

        foreach ($files as $file) {
            $filename = $dir . '/' . $file;

            if (is_file($filename)) {
                $info = pathinfo($file);

                if (isset($info['filename'])) {
                    $controllerName = $info['filename'];
                    $class = new \ReflectionClass('\App\Http\Controllers\Admin\\' . $controllerName);

                    if ($class->hasProperty('menus')) {
                        $menus = $class->getStaticPropertyValue('menus');
                        self::addMenu($menus);
                    }
                }
            }
        }
    }
}
