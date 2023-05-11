<?php

namespace App\Core\DB;

use Illuminate\Support\Facades\DB;

class AutoSchema
{
    protected $table;
    protected $model;
    protected $exception;
    protected $message;
    protected $sqlErrorCode;

    public function __construct(ModelCore $model, \Throwable $exception)
    {
        $this->table = $model->getTable();
        $this->message = $exception->getMessage();
        $this->model = $model;
    }

    public function handle(): bool
    {
        if (strpos($this->message, 'Base table or view not found') !== false) {
            DB::select($this->getCreateSql());
            return true;
        } else if (strpos($this->message, 'Unknown column') !== false) {
            $table = $this->table;
            $cols = DB::select("SHOW COLUMNS FROM `$table`");
            $colMap = [];
            foreach ($cols as $col) {
                $colMap[$col->Field] = true;
            }

            DB::select($this->getAlterSql());
            return true;
        }

        return false;
    }

    private function getSqlFields(bool $isAlter): array
    {
        $primaryKey = $this->model->getKeyName();
        $data = $this->model->toArray();
        $table = $this->table;
        $colMap = [];
        if ($isAlter) {
            $cols = DB::select("SHOW COLUMNS FROM `$table`");
            $colMap = [];
            foreach ($cols as $col) {
                $colMap[$col->Field] = true;
            }
        }

        $ignored = [
            $primaryKey => true,
            'created_at' => true,
            'updated_at' => true
        ];

        $fields = [];
        foreach ($data as $k => $v) {
            if (!isset($ignored[$k]) && !isset($colMap[$k])) {
                $fields[] = [$k, $v];
            }
        }

        $sqlFields = array_map(function ($e) use ($isAlter) {
            list($k, $v) = $e;
            $type = gettype($v);
            $sqlType = 'TEXT';

            if ($type === 'string') {
                $len = max(250, mb_strlen($v));

                if ($len > 1000) {
                    $sqlType = 'TEXT';
                } else {
                    $sqlType = 'VARCHAR(' . $len . ')';
                }
            } else if ($type === 'integer') {
                if ($k === 'status') {
                    $sqlType = 'TINYINT(4) UNSIGNED';
                } else {
                    $sqlType = 'INT NULL';
                }

            } elseif ($type === 'double') {
                $sqlType = 'DECIMAL(10,2) NULL';
            }

            if ($isAlter) {
                return "ADD COLUMN  `$k` $sqlType";
            }

            return "`$k` $sqlType";
        }, $fields);

        return $sqlFields;
    }

    private function getAlterSql(): string
    {
        $table = $this->table;
        $sqlFields = $this->getSqlFields(true);
        $sqlFieldStr = implode(", ", $sqlFields);

        return "ALTER TABLE `$table` $sqlFieldStr; ";
    }

    public function getCreateSql(): string
    {
        $table = $this->table;
        $primaryKey = $this->model->getKeyName();
        $sqlFields = $this->getSqlFields(false);
        $sqlFieldStr = implode(",", $sqlFields);


        $sql = "CREATE TABLE `$table`( `$primaryKey` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                                        $sqlFieldStr,
                                        `created_at` DATETIME,
                                        `updated_at` DATETIME,
                                        PRIMARY KEY (`$primaryKey`) ); ";

        return $sql;
    }
}
