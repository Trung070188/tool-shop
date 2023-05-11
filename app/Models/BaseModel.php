<?php

namespace App\Models;

use App\Core\DB\ModelCore;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Class BaseModel.
 *
 * @method static Builder where($column, $operator = null, $value = null, $boolean = 'and')
 * @method static Builder whereIn($column, $values, $boolean = 'and', $not = false)
 * @method static Builder select($columns = ['*'])
 * @method static Builder selectRaw($expression, array $bindings = [])
 * @method static Builder whereFindInSet(string $field, $value)
 * @method static Builder orWhereFindInSet(string $field, $value)
 * @method static Builder orderBy($column, $direction = 'asc')
 * @method static static find($id)
 * @method static static first()
 * @method static \Illuminate\Support\Collection|static[] get()
 */
class BaseModel extends ModelCore
{
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
}
