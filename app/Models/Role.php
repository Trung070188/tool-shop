<?php

namespace App\Models;


 /**
 * @property int       $id
 * @property string    $code
 * @property string    $name
 * @property string    $display_name
 * @property \DateTime $created_at
 * @property \DateTime $updated_at
 */
class Role extends BaseModel
{
    const SUPER_USER = 1;

    protected $table = 'roles';
    protected $fillable = [
    'code',
    'name',
    'display_name',
];
}
