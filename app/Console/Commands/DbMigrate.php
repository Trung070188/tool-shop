<?php
/**
 * Simple DB Migration
 * @author quantm@ominext.com
 * @created 2020/02/23
 */

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use PhpMyAdmin\SqlParser\Components\CreateDefinition;
use PhpMyAdmin\SqlParser\Statements\CreateStatement;
use PhpMyAdmin\SqlParser\Components\Expression;

class DbMigrate extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'DbMigrate {action?} {table?} {--force} {--all} {--drop}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';
    private $path;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->path = base_path('/database/scheme');

        if (!is_dir($this->path)) {
            mkdir($this->path, 0755, true);
        }
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $action = $this->argument('action');

        if (!$action) {
            $action = 'diff';
        }

        if (!method_exists($this, $action)) {
            $this->error("Method $action does not exist");
            return;
        }

        $this->info("DbMigrate 1.0.1");
        //$this->warn("WARNING: PRIMARY KEY CHANGES WILL BE IGNORED");
        //$this->warn("WARNING: FOREIGN KEY IS NOT SUPPORTED");

        $this->$action();
    }

    /**
     * Gets current stored table
     * @return array
     * @throws \Exception
     */
    private function getStoredTables()
    {
        $files = glob($this->path . '/*.sql');
        $tables = [];
        foreach ($files as $file) {
            $info = pathinfo($file);
            if (!$info) {
                continue;
            }

            $sql = file_get_contents($file);
            $tableInfo = $this->parse($sql);
            $name = $tableInfo['name'];
            if (isset($tables[$tableInfo['name']])) {
                throw new \Exception("Duplicate table: $name. In file:  $file");
            }
            $tables[$name] = $sql;
        }

        return $tables;
    }

    /**
     * Diffs table in order to alter
     * @throws \Exception
     */
    public function diff()
    {
        $versions = $this->loadDbVersion();
        if ($versions) {
            if ($versions['current']['version'] !== $versions['stored']['version']) {
                $this->warn('WARNING: Your db version: ' . $versions['current']['version'] . ' is different from stored version: ' . $versions['stored']['version']);
            }
        }

        $force = $this->option('force');
        $tables = $this->getCurrentTableStates();

        $currentTables = [];

        $diffSql = [];
        foreach ($tables as $table) {

            $tableName = $table['name'];
            $currentTables[$tableName] = true;
            $storedInfo = $this->getStoredTableInfo($tableName);
            $diffs = $this->_diff($table, $storedInfo);
            if (!empty($diffs)) {
                $diffSql = array_merge($diffSql, $diffs);
            }
        }

        $storedTable = $this->getStoredTables();
        foreach ($storedTable as $_tableName => $sql) {
            if (!isset($currentTables[$_tableName])) {
                $diffSql[] = [
                    'type' => 'created',
                    'table' => $_tableName,
                    'sql' => $sql
                ];
            }
        }

        if (empty($diffSql)) {
            $this->info("NO CHANGES");
            return;
        }

        $allowDrop = $this->option('drop');
        if (!$force) {
            if ($allowDrop) {
                echo "Apply changes:\n";
            } else {
                echo "Apply changes (DROP command will be ignored):\n";
            }

            $warn = [];
            foreach ($diffSql as $_idx => $d) {
                $idx = $_idx + 1;
                if ($d['type'] === 'created') {
                    $warn[] = "$idx. CREATE TABLE `{$d['table']}`(...)";
                } else {
                    $dsql = $d['sql'];
                    if (isset($d['origin'])) {
                        $dsql .= "\n(ORIGIN: " . $d['origin'] . ')';
                    }
                    $warn[] = $idx . '. ' . $dsql;
                }
            }
            $this->warn(implode("\n", $warn));
            $ans = $this->ask("Type y/n");
            if ($ans === 'y') {
                $time_start = microtime(true);
                foreach ($diffSql as $d) {
                    $this->exec($d, $allowDrop);
                }

                $execution_time = (microtime(true) - $time_start);
                $this->info("SUCCEED in $execution_time seconds");
            } else {
                $this->warn("EXIT");
            }
        } else {
            $time_start = microtime(true);
            foreach ($diffSql as $d) {
                $this->exec($d, $allowDrop);
            }
            $execution_time = (microtime(true) - $time_start);
            $this->info("SUCCEED in $execution_time seconds");
        }
    }


    public function _diff($table, $storedInfo)
    {
        $tableName = $table['name'];
        // $currentTables[$tableName] = true;
        //echo "Diff $tableName...";
        $targetFields = $table['fields'];
        $diffSql = [];

        if ($storedInfo === false) {
            $diffSql[] = [
                'type' => 'drop',
                'table' => $tableName,
                'sql' => "DROP TABLE `$tableName`"
            ];
            return;
        }

        $currentFields = $storedInfo['fields'];

        $diffFields = array_diff($currentFields, $targetFields);
        $fieldsList = array_keys($currentFields);

        if (!empty($diffFields)) {
            $originSql = null;
            foreach ($diffFields as $_field => $diff) {
                if (isset($targetFields[$_field])) {
                    $originSql = $targetFields[$_field];
                    $sql = "ALTER TABLE `$tableName` MODIFY $diff;";
                } else {
                    $indexOf = array_search($_field, $fieldsList);
                    $after = '';
                    if ($indexOf !== false && $indexOf > 1) {
                        $previousField = $fieldsList[$indexOf - 1];
                        if (isset($targetFields[$previousField])) {
                            $after = " AFTER `$previousField`";
                        }
                    }

                    $sql = "ALTER TABLE `$tableName` ADD COLUMN $diff $after;";
                }
                $diffSql[] = [
                    'type' => 'alter',
                    'table' => $tableName,
                    'sql' => $sql,
                    'origin' => $originSql
                ];
            }
        }

        foreach ($targetFields as $_field => $sql) {
            if (!isset($currentFields[$_field])) {
                $sql = "ALTER TABLE `$tableName` DROP COLUMN `$_field`;";
                $diffSql[] = [
                    'type' => 'alter',
                    'table' => $tableName,
                    'sql' => $sql
                ];
            }
        }

        $currentKeys = $storedInfo['keys'];
        $targetKeys = $table['keys'];

        foreach ($currentKeys as $indexName => $index) {
            if (!isset($targetKeys[$indexName])) {
                $indexSql = $this->getIndexSql($index);
                if ($indexSql) {
                    $diffSql[] = [
                        'type' => 'addindex',
                        'table' => $tableName,
                        'sql' => "ALTER TABLE `$tableName` ADD $indexSql"
                    ];
                }
            } else {
                if ($targetKeys[$indexName]['sql'] != $index['sql']) {
                    $indexSql = $this->getIndexSql($index);
                    if ($indexSql) {
                        $diffSql[] = [
                            'type' => 'replaceindex',
                            'table' => $tableName,
                            'sql' => "ALTER TABLE `$tableName` DROP INDEX `$indexName`, ADD $indexSql"
                        ];
                    }
                }
            }
        }

        foreach ($targetKeys as $indexName => $index) {
            if (!isset($currentKeys[$indexName])) {
                $diffSql[] = [
                    'type' => 'dropindex',
                    'table' => $tableName,
                    'sql' => "ALTER TABLE `$tableName` DROP INDEX `$indexName`; "
                ];
            }
        }

        return $diffSql;
    }

    /**
     * Exec command
     * @param $diff
     * @param $allowDrop
     */
    private function exec($diff, $allowDrop)
    {
        if (!$allowDrop && $diff['type'] === 'drop') {
            echo "IGNORED: {$diff['sql']}\n";

        } else {
            try {
                echo "Executing: ";
                $this->warn($diff['sql']);
                DB::selectOne($diff['sql']);
                $this->info("DONE");
            } catch (\Exception $e) {
                $this->error($e->getMessage());
            }
        }
    }

    /**
     *
     */
    public function save()
    {
        $ans = $this->argument('table');
        $isAll = $this->option('all');

        if (!$isAll && empty($ans)) {
            $this->warn("Missing table.");
            $this->info("Example usage: php artisan DbMigrate save users");
            $this->info("Example usage: php artisan DbMigrate save --all");
            return;
        }

        $tables = $this->getCurrentTableStates();
        if (empty($tables)) {
            $this->warn("No table to save");
            return;
        }

        $tableMap = [];
        foreach ($tables as $table) {
            $tableMap[$table['name']] = true;
        }

        if (!$isAll && !isset($tableMap[$ans])) {
            $this->error("Table `$ans` does not exist");
            return;
        }

        foreach ($tables as $table) {
            if (!$isAll && $table['name'] !== $ans) {
                continue;
            }

            $tableName = $table['name'];
            echo "Checking $tableName...";
            $filename = base_path('database/scheme/' . $table['name'] . '.sql');
            if (file_exists($filename)) {
                $current = file_get_contents($filename);
                if ($this->normalized($current) !== $table['sql']) {

                    file_put_contents($filename, $table['sql']);
                    $this->info("UPDATED");
                } else {
                    $this->warn("NO CHANGES");
                }
            } else {
                file_put_contents($filename, $table['sql']);
                $this->info("CREATED");
            }
        }
    }

    public function savedbversion()
    {
        $versions = DB::select('SHOW VARIABLES LIKE "%version%";');
        $data = [];
        foreach ($versions as $v) {
            $data[$v->Variable_name] = $v->Value;
        }
        file_put_contents($this->path . '/_version.json', json_encode($data, 128));
        $this->info('SUCCEED');
    }

    private function loadDbVersion()
    {
        try {
            $stored = json_decode(file_get_contents($this->path . '/_version.json'), true);
            $versions = DB::select('SHOW VARIABLES LIKE "%version%";');
            $current = [];
            foreach ($versions as $v) {
                $current[$v->Variable_name] = $v->Value;
            }

            return compact('stored', 'current');
        } catch (\Exception $e) {
        }

        return null;
    }

    private function normalized($string)
    {
        return preg_replace('~\r\n?~', "\n", $string);
    }

    /**
     * Save current table states
     * @param $table
     * @return array | bool
     */
    private function getStoredTableInfo($table)
    {
        $filename = $this->path . '/' . $table . '.sql';
        if (!is_file($filename)) {
            return false;
        }

        try {
            $sql = file_get_contents($filename);
            return $this->parse($sql);
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }

        return false;
    }

    /**
     * Gets current tables states
     */
    private function getCurrentTableStates()
    {
        $tables = DB::select("SHOW TABLES");
        $results = [];
        foreach ($tables as $table) {
            $table = (array)$table;
            $tableName = array_values($table)[0];
            //  echo "Added $tableName...";

            $res = (array)DB::selectOne("SHOW CREATE TABLE `$tableName`");

            $sql = $this->cleanSql($res['Create Table']);
            $parsed = $this->parse($sql);
            $parsed['sql'] = $sql;
            $results[] = $parsed;
        }

        return $results;
    }

    private function cleanSql($sql)
    {
        return preg_replace('/ AUTO_INCREMENT=\d+ /', ' ', trim($sql));
    }

    /**
     * Parse create $sql
     * @throws \Exception
     */
    private function parse($sql)
    {
        /**
         * @var CreateStatement $stmt
         * @var CreateDefinition $field
         * @var Expression $expr
         */
        $parser = new \PhpMyAdmin\SqlParser\Parser($sql);
        if (empty($parser->statements)) {
            throw new \Exception("Invalid Create statement.1");
        }

        $stmt = $parser->statements[0];

        if (!($stmt instanceof CreateStatement)) {
            throw new \Exception("Invalid Create statement.2");
        }

        $tableFields = [];
        $tableKeys = [];
        $textsFields = [
            'TEXT' => true,
            'MEDIUMTEXT' => true,
            'LONGTEXT' => true
        ];
        foreach ($stmt->fields as $field) {
            if ($field->options) {
                $newOptions = [];
                foreach ($field->options->options as $idx => $option) {

                    if (isset($option['name']) && $option['name'] === 'DEFAULT') {
                        // MariaDB will expose CURRENT_TIMESTAMP() instead of CURRENT_TIMESTAMP
                        if ($option['value'] === 'current_timestamp()') {
                            $option['value'] = 'CURRENT_TIMESTAMP';
                        }

                        // MariaDB will expose number instead of string, normalized it to string
                        if (is_numeric($option['value'])) {
                            $option['value'] = "'" . $option['value'] . "'";
                        }

                        $expr = $option['expr'];
                        $expr->expr = $option['value'];
                        $option['expr'] = $expr;
                        // IGNORED  DEFAULT NULL FOR TEXT FIELD
                        if (isset($textsFields[$field->type->name])) {
                            continue;
                        }
                    }

                    $newOptions[] = $option;
                }
                $field->options->options = $newOptions;
            }

            $built = CreateDefinition::build($field);
            if ($field->key) {
                $tableKeys[$field->key->name] = [
                    'type' => $field->key->type,
                    'sql' => $built,
                ];
            } else {
                $tableFields[$field->name] = $built;
            }
        }

        return [
            'name' => $stmt->name->table,
            'keys' => $tableKeys,
            'fields' => $tableFields
        ];
    }

    private function getIndexSql($index)
    {
        $indexSql = false;
        if ($index['type'] === 'UNIQUE KEY') {
            $indexSql = preg_replace('/^UNIQUE KEY /', 'UNIQUE INDEX', $index['sql']);
        } else if ($index['type'] === 'FULLTEXT KEY') {
            $indexSql = preg_replace('/^FULLTEXT KEY /', 'FULLTEXT INDEX', $index['sql']);
        } else if ($index['type'] === 'KEY') {
            $indexSql = preg_replace('/^KEY /', 'INDEX', $index['sql']);
        } else if ($index['type'] === 'PRIMARY KEY') {
            return false;
        }

        return $indexSql;
    }
}
