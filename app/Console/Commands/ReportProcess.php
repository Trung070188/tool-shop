<?php

namespace App\Console\Commands;

use App\Helpers\PhpDoc;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;


class ReportProcess extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'Report {action} {--force}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    public function handle() {
        $action = $this->argument('action');
        $this->$action();
    }

    public function summary() {
        $tables = config('querybuilder.whitelist.tables');


        $reports = [];
        $maxLen = 0;
        foreach ($tables as $table) {
            $len = strlen($table);
            if ($len > $maxLen) {
                $maxLen = $len;
            }

            try {

                $count = DB::table($table)->count();
                $reports[] = [$table, $count];
            } catch (\Throwable $e) {
                $this->info("Table $table -> " . $e->getMessage());
            }
        }

        $maxLen += 5;


        usort($reports, function($a,$b) {
            return $b[1] - $a[1];
        });

        foreach ($reports as $report) {
            list($tableName, $count ) = $report;
            $padTable = str_pad($tableName, $maxLen, '.');
            $this->info("$padTable -> $count");
        }
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function all()
    {
        if ($this->option('force')) {
            $tables = config('querybuilder.whitelist.tables');
            foreach ($tables as $table) {

                if (substr($table, 0, 7) === 'report_') {
                    $this->info("TRUNCATE TABLE $table");
                    DB::table($table)->truncate();
                }

            }

            $this->info("TRUNCATE TABLE report_process_states");
            DB::table('report_process_states')->truncate();
            die;
        }

        $this->spawn('php artisan ReportWalletStatusProcess IndexAll auto');
        $this->spawn('php artisan ReportWalletStatusProcess MainProcess auto');
        $this->spawn('php artisan ReportTopCustomerProcess auto');
        $this->spawn('php artisan ReportTransactionStatusProcess auto');
        $this->spawn('php artisan ReportTransactionTypesProcess auto');
    }

    public function spawn(string $cmd) {
        // while (@ ob_end_flush()); // end all output buffers if any

        $proc = popen($cmd, 'r');

        while (!feof($proc))
        {
            echo fread($proc, 4096);
            @ flush();
        }
    }
}
