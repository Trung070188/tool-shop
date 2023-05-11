<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;

/**
 * @property int $id
 * @property string $username
 * @property string $name
 * @property string $full_name
 * @property string $phone
 * @property string $email
 * @property string $password
 * @property string $remember_token
 * @property \DateTime $created_at
 * @property \DateTime $updated_at
 * @property \DateTime $deleted_at
 */
class User extends Authenticatable
{
    protected $table = 'users';
    use HasFactory, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'sap_id',
        'code',
        'username',
        'name',
        'position_id',
        'level',
        'birthday',
        'phone',
        'address',
        'email',
        'direct_boss_id',
        'nationality',
        'start_work_date',
        'end_work_date',
        'department',
        'member_unit_id',
        'region',
        'status',
        'type',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'remember_token',
    ];

    public function positions()
    {
        return $this->hasOne(Position::class, 'id', 'position_id')->select('id', 'position_title');
    }

    public function memberUnit()
    {
        return $this->hasOne(MemberUnit::class, 'id', 'member_unit_id')->select('id', 'member_unit_name');
    }

    /**
     * @param $id
     * @return bool
     */
    public function hasGroup($id): bool
    {
        static $map = [];

        if (empty($map)) {
            $userGroups = DB::select('SELECT * FROM user_groups p
INNER JOIN groups g ON g.`id`=p.`group_id`
WHERE p.`user_id`=?', [$this->id]);
            foreach ($userGroups as $g) {
                $map[$g->group_id] = true;
            }
        }

        if (isset($map[Role::SUPER_USER])) {
            return true;
        }

        return isset($map[$id]);
    }

    /**
     * Scope a query to only include popular users.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeCreatedIn($query, $dateRange)
    {
        $createdRange = date_parse_range($dateRange);

        if ($createdRange) {
            $query->where('created_at', '>=', $createdRange['start'] . ' 00:00:00')
                ->where('created_at', '<=', $createdRange['end'] . ' 23:59:59');
        }

        return $query;
    }

    public function getAvatar()
    {
        if ($this->avatar) {
            return $this->avatar;
        }

        return asset('/assets/avatar/?name=' . urlencode($this->name));
    }
}
