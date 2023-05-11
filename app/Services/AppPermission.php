<?php

namespace App\Services;

use App\Models\Permission;
use App\Models\Role;
use App\Models\RolePermission;
use App\Models\User;
use App\Models\UserPermission;
use App\Models\UserRole;
use App\Types\PermissionInfo;
use Illuminate\Http\Request;


class AppPermission
{
    private $debugInfo = [];
    private $requiredPermissions = [];

    public static function getPermissionInfo(User $user): PermissionInfo
    {
        $info = new PermissionInfo();

        $allPermissions = collect();
        $roleIDs = UserRole::where('user_id', $user->id)
            ->with('role')
            ->get()->pluck('role_id')->toArray();

        $roleIDMap = array_flip($roleIDs);
        $info->isRoot = $user->is_root == 1;
        $info->isAdmin = isset($roleIDMap[Role::SUPER_USER]);

        $userPermissions = UserPermission::query()
            ->where('user_id', $user->id)
            ->get();

        foreach ($userPermissions as $userPermission) {
            $allPermissions[$userPermission->permission_id] = Permission::findPermissionById($userPermission->permission_id);
        }

        if (count($roleIDs) > 0) {
            $rolePermissions = RolePermission::query()
                ->whereIn('role_id', $roleIDs)
                ->get();

            foreach ($rolePermissions as $rolePermission) {
                $allPermissions[$rolePermission->permission_id] = Permission::findPermissionById($rolePermission->permission_id);
            }
        }


        if (isset($allPermissions[Permission::ROOT_ID])) {
            $info->isAdmin = true;
        } else {
            $permissionIDs = $allPermissions->values()->pluck('id')->toArray();
            if (!empty($permissionIDs)) {
                $permissions = Permission::query()
                    ->selectRaw('id,module,name,display_name,parent_id')
                    ->with('children:id,module,name,parent_id')
                    ->whereIn('id', $permissionIDs)
                    ->get();
                $info->permissions = getPermissionNameMap($permissions);
            }

        }


        return $info;
    }

    public static function getInstance() {
        static $e;
        if (!$e) {
            $e = new AppPermission();
        }

        return $e;
    }

    public function hasPermission(User $user, Permission $permission, $checkLevel = 0): bool
    {
        $userPermission = UserPermission::where('user_id', $user->id)
            ->where('permission_id', $permission->id)
            ->first();

        if ($userPermission) {
            $this->debugInfo[] = "[$checkLevel][1] Tồn tại bản ghi trong UserPermission";
            return true;
        }

        $roleIDs = UserRole::where('user_id', $user->id)->get()->pluck('role_id')->toArray();
        $roleIDMap = array_flip($roleIDs);

        if (isset($roleIDMap[Role::SUPER_USER])) {
            $this->debugInfo[] = "[$checkLevel][2] Có role ROOT";
            return true;
        }

        $rolePermissions = collect([]);

        if (!empty($roleIDs)) {
            $rolePermissions = RolePermission::query()
                ->where('permission_id', $permission->id)
                ->whereIn('role_id', $roleIDs)->get();
        }

        if ($rolePermissions->count() > 0) {
            $this->debugInfo[] = "[$checkLevel][3] Tồn tại trong RolePermission";
            return true;
        }


        $parent = Permission::findPermissionById($permission->parent_id);

        if ($parent) {
            $this->debugInfo[] = "[$checkLevel][5] " . $permission->name . ' không có quyền';

            if (self::hasPermission($user, $parent, $checkLevel + 1)) {
                $this->debugInfo[] = "[$checkLevel][4] Parent {$parent->name} có quyền";
                return true;
            }
        }

        return false;

    }

    public function validate(string $class, string $action, Request $request): bool
    {

        $key = $class . '@' . $action;

        if (isset($this->allowAll[$key])) {
            return true;
        }

        /**
         * @var Permission $permission
         */
        $permission = Permission::findOrCreate($class, $action);
        $this->requiredPermissions[] = $permission;

        $user = auth_user();

        if (!$user) {
            return false;
        }

        return $this->hasPermission($user, $permission);
    }


    public function getRequiredPermissions(): array
    {
        return $this->requiredPermissions;
    }

    public function getDebugInfo(): array
    {
        return $this->debugInfo;
    }
}
