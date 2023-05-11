<?php

namespace App\Models;


 /**
 * @property int       $id
 * @property string    $name
 * @property string    $package_id
 * @property string    $icon
 * @property int       $price
 * @property string    $os
 * @property int       $customer_id
 * @property string    $type
 * @property \DateTime $created_at
 * @property \DateTime $updated_at
 * @property int       $status
 */
class Campaign extends BaseModel
{
    protected $table = 'campaigns';
    protected $fillable = [
    'name',
    'package_id',
    'icon',
    'price',
    'os',
    'customer_id',
    'type',
    'status',
];
}
