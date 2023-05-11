<?php


namespace App\Models;


class UserRole extends BaseModel
{
    protected $table = 'user_roles';

    public function role() {
        return $this->belongsTo(Role::class, 'role_id');
    }
}
