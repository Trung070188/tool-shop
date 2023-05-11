<?php

namespace App\Core\DB;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ModelCore extends Model
{
    protected $autoSchema = true;

    public function save(array $options = [])
    {
        try {
            return parent::save($options);
        } catch (\Throwable $e) {
            if (!$this->autoSchema) {
                throw $e;
            }

            $isProd = config('app.env') === 'production';
            if ($isProd || !config('database.autoschema')) {
                throw $e;
            }

            $autoSchema = new AutoSchema($this, $e);
            if ($autoSchema->handle()) {
                return parent::save($options);
            } else {
                throw $e;
            }
        }
    }
}
