<?php

namespace App\Console\Commands;

use App\Models\SnapshotConfig;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SbvSnapshot extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'SbvSnapshot {--limit=} {--force} {--memory=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {

        $limit = (int) $this->option('limit');
        $memory = (int) $this->option('memory');
        $limit = getValueInRange($limit, 1000, 20000);
        $memory = getValueInRange($memory, 128, 4096);

        $this->info("SbvSnapshot Started");
        $this->info("ChunkSize = $limit (From 1000 -> 20000), MemoryLimit = $memory M (From 128MB -> 4096MB)");


        $snapshotConfigs = DB::table('snapshot_configs')->where('status', 1)->get();
        $dbSnapshot = dbConnection('snapshot');
        ini_set('memory_limit', $memory . 'M');
        /**
         * @var SnapshotConfig $config
         */
        foreach ($snapshotConfigs as $config) {
            try {
                $snapshotTableName = $this->generateSnapshotTable($config);
                $db = dbConnection($config->connection);
                $total = $db->table($config->table)
                    ->selectRaw($config->fields)
                    ->where(function($q) use($config) {
                        if (!empty($config->time_field)) {
                            $q->where($config->time_field, '>=', $config->time_from)
                                ->where($config->time_field, '<=', $config->time_to);
                        }
                    })
                    ->count();
                $inserted = 0;

                $db->table($config->table)
                    ->selectRaw($config->fields)
                    ->where(function($q) use($config) {
                        if (!empty($config->time_field)) {
                            $q->where($config->time_field, '>=', $config->time_from)
                                ->where($config->time_field, '<=', $config->time_to);
                        }
                    })
                    ->orderBy($config->order_field, 'ASC')
                    ->chunk($limit, function($_entries)
                    use($dbSnapshot, $snapshotTableName, &$inserted, $total, &$exists) {

                        $entryChunk = $_entries->chunk(2000);

                        foreach ($entryChunk as $entries) {
                            $bulkData = [];
                            foreach ($entries as $entry) {
                                /*if (isset($exists[$entry->id])) {
                                    throw new \Exception("ID " . $entry->id . ' already exists');
                                }
                                $exists[$entry->id] = $entry->create_on;*/
                                $bulkData[] = (array) $entry;
                            }

                            $inserted += count($bulkData);

                            $dbSnapshot->table($snapshotTableName)
                                ->insert($bulkData);
                        }
                        $percent = get_percent($inserted, $total);
                        $this->info("[$percent%] Inserted $inserted/$total");
                    });
            } catch (\Throwable $ex) {
                $this->error($ex->getMessage());
                $this->error(json_encode(exception_truncate($ex->getTraceAsString()), JSON_PRETTY_PRINT));
            }
        }

    }

    private function getPostgresSchema($config) {
        $pgSql = "select column_name, data_type, character_maximum_length, column_default, is_nullable
    from INFORMATION_SCHEMA.COLUMNS where table_name = ? ORDER BY ordinal_position";
        $_schema = DB::connection($config->connection)
            ->select($pgSql, [$config->table]);
        $schema = [];
        $dataTypeMap = [
            'uuid' => 'VARCHAR(36)',
            'character varying' => 'TEXT',
            'boolean' => 'TINYINT(1)',
            'timestamp without time zone' => 'DATETIME'
        ];
        $fieldDataMap = [
            'sender_wallet' => 'VARCHAR(36)',
            'receiver_wallet' => 'VARCHAR(36)',
        ];

        foreach ($_schema as $item) {
            $dataType = $dataTypeMap[$item->data_type] ?? $item->data_type;

            if (isset($fieldDataMap[$item->column_name])) {
                $dataType =$fieldDataMap[$item->column_name];
            } else {
                if ($item->character_maximum_length !== null) {
                    $dataType = 'VARCHAR(' . $item->character_maximum_length . ')';
                }
            }

            $schema[] = (object)[
                'Field' => $item->column_name,
                'Type' => $dataType,
                'Null' => $item->is_nullable,
                'Default' => $item->column_default,
                'Extra' => ''
            ];
        }

        return $schema;
    }

    /**
     * @param $config
     * @return string
     * @throws \Exception
     */
    private function generateSnapshotTable(SnapshotConfig | \stdClass $config) {

        $dbSnapshot = dbConnection('snapshot');

        $tableInSnapshots = $dbSnapshot->select("SHOW TABLES");
        $tableNameMap = [];
        foreach($tableInSnapshots as $t) {
            $tableName = last((array)$t);
            $tableNameMap[strtoupper($tableName)] = true;
        }

        $snapshotTableName = strtoupper($config->connection. "__" . $config->table);
        if (isset($tableNameMap[$snapshotTableName])) {

            if ($this->option('force')) {
                $this->info("DROP TABLE $snapshotTableName");
                $dbSnapshot->select("DROP TABLE $snapshotTableName\n");
            } else {
                throw new \Exception("Table $snapshotTableName already exists");
            }
        }

        $db = dbConnection($config->connection);

        $isEWallet = $config->connection === 'EWALLET';

        if ($isEWallet) {
            $columns = $this->getPostgresSchema($config);
        } else {
            $columns = $db->select('SHOW COLUMNS FROM ' . $config->table);
        }

        $indexMap = [];
        if (!$isEWallet) {
            $indexes = $db->select("SHOW INDEX FROM " . $config->table);
        } else {
            $indexes = $this->getPostgresIndex($config);
        }


        foreach ($indexes as $index) {
            $indexMap[$index->Column_name][] = $index;
        }


        $colMap = [];
        foreach ($columns as $column) {
            $colMap[$column->Field] = $column;
        }


        $colSQLs = [];
        $selectedIndexes = [];
        if ($config->fields === '*') {
            foreach ($columns as $column) {
                $colSQLs[] = $this->getFieldDescribe($column);
            }

            $selectedIndexes = $indexes;


        } else {
            $fields = array_map('trim', explode(',', $config->fields));
            foreach ($fields as $field) {

                if (!isset($colMap[$field])) {
                    throw new \Exception("Column $field not found");
                }
                if (isset($indexMap[$field])) {
                    $selectedIndexes = array_merge($selectedIndexes, $indexMap[$field]);
                }
                $column = $colMap[$field];

                $colSQLs[] = $this->getFieldDescribe($column);
            }

        }

        $groupByKeyNames = [];
        foreach ($selectedIndexes as $index) {
            $groupByKeyNames[$index->Key_name][] = $index;
        }

        $keySQls = [];
        foreach ($groupByKeyNames as $keyName => $keys) {
            $keySQls[] = $this->getKeyDescribe($keyName, $keys);
        }



        $createSQl = "CREATE TABLE `$snapshotTableName` (\n"
            .implode(",\n", $colSQLs)
            .",\n"
            .implode(",\n", $keySQls). "\n)\nENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

        echo ("CREATE TABLE $snapshotTableName...");
        dbConnection('snapshot')->select($createSQl);
        echo("DONE\n");

        return $snapshotTableName;
    }

    private function getPostgresIndex($config) {
        $indexes = dbConnection($config->connection)->select(" select *  from pg_indexes where tablename = ?", [
            $config->table
        ]);

        $returnIndexes = [];
        foreach ($indexes as $index) {

            $indexColName = $this->parsePostGresIndex($index);
            $keyName = $indexColName;
            if (str_contains($index->indexname, 'pkey')) {
                $keyName = 'PRIMARY';
            }

            $returnIndexes[] = (object) [
                'Column_name' => $indexColName,
                'Non_unique' => 1,
                'Key_name' => $keyName,
                'Seq_in_index' => 0,
            ];
        }

        return $returnIndexes;
    }

    private function parsePostgresIndex($index) {
        $def = $index->indexdef;
        $p1 = strpos($def, '(');
        $p2  =strpos($def, ')', $p1);
        $key = substr($def, $p1, $p2);
        $key = trim($key, '()');
        list($col) = explode(" ", $key);
        return $col;
    }

    private function getKeyDescribe($keyName, $keys) {
        usort($keys, function($a,$b) {
           return $a->Seq_in_index - $b->Seq_in_index;
        });

        $fields = [];
        foreach ($keys as $key) {
            $fields[] = "`". $key->Column_name . "`";
        }

        $Non_unique = $keys[0]->Non_unique;

        $fieldSql = implode(',', $fields);

        if ($keyName === 'PRIMARY') {
            return "PRIMARY KEY ($fieldSql)";
        } else if ($Non_unique === 0) {
            return "UNIQUE KEY `$keyName` ($fieldSql)";
        } else {
            return "KEY `$keyName` ($fieldSql)";
        }
    }


    private function getFieldDescribe($column) {
        $field = $column->Field;
        $type = $column->Type;
        $null = $column->Null === 'NO' ? 'NOT NULL': '';
        $extra  =$column->Extra;

        $defaultSQl = '';
        if ($column->Default === null) {
            if ($column->Null === "YES") {
                $defaultSQl = "DEFAULT NULL";
            }
        } else {
            if (strtolower($column->Default) === 'current_timestamp()') {
                $defaultSQl = "DEFAULT current_timestamp()";
            } else {
                if (is_numeric($column->Default)) {
                    $defaultSQl = "DEFAULT {$column->Default}";
                } else {
                    $defaultSQl = "DEFAULT '{$column->Default}'";
                }
            }
        }

        $sql = "`$field` $type $defaultSQl $null $extra";
        return trim($sql);
    }

}
