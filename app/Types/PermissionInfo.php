<?php

namespace App\Types;

/**
 * @property boolean $isRoot
 * @property boolean $isAdmin
 * @property array   $permissions
 */
class PermissionInfo implements \JsonSerializable
{
    public bool $isRoot = false;
    public bool $isAdmin = false;
    public array $permissions = [];

    public function jsonSerialize() {
        return [
            'isRoot' => $this->isRoot,
            'isAdmin' => $this->isAdmin,
            'permissions' => $this->permissions,
        ];
    }
}
