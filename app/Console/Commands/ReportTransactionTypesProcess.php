<?php

namespace App\Console\Commands;

use App\Models\ReportTransactionType;
use App\Models\TransactionType;
use Illuminate\Console\Command;
use Illuminate\Database\ConnectionInterface;
use \Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class ReportTransactionTypesProcess extends Command
{


    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ReportTransactionTypesProcess {fromDate} {--force}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    public function handle()
    {
        $env = config_env('REPORT_ENV', 'local');

        $tableState = 'report_transaction_types';
        if ($this->option('force')) {
            $this->info("TRUNCATE TABLE report_transaction_types");
            DB::table('report_transaction_types')->truncate();
            $deleted = DB::table('report_process_states')
                ->where('class', $tableState)
                ->delete();
            $this->info("DELETED $deleted FROM report_process_states");
        }

        $this->info("[$env]ReportTransactionTypesProcess");

        $fromDate = $this->argument('fromDate');

        forEachMonth($tableState, function ($year, $month) {
            $this->process($year, $month);
        }, $fromDate);
    }

    public static function createBaseQuery(ConnectionInterface $o2oDb,$startTime , $endTime, $sysOfferTransCodes): Builder
    {
        return $o2oDb->table('ORDER_BASE_TRANS_M')
            ->selectRaw('COUNT(*) `total_count`, SUM(TRANS_AMOUNT) `total_amount`, DATE(TRANS_DATETIME) date')
            ->where('TRANS_STATUS', 1)
            ->where('TRANS_DATETIME', '>=', $startTime)
            ->where('TRANS_DATETIME', '<=', $endTime)
            ->where(function(Builder $query) {
                $query->where('SRC_ACCOUNT_TYPE', '=', CUSTOMER_ACCOUNT_TYPE_POSTPAY)
                    ->orWhere('DES_ACCOUNT_TYPE', '=', CUSTOMER_ACCOUNT_TYPE_POSTPAY);
            })
            ->whereIn('SYS_OFFER_TRANS_CODE', $sysOfferTransCodes)
            ->groupByRaw('DATE(TRANS_DATETIME)');
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function process($year, $month)
    {
        $o2oDb = dbConnection('O2O');
        $transTypes = TransactionType::query()->with('transactionTypeCodes')->get();


        foreach ($transTypes as $transType) {
            $sysOfferTransCodes = $transType->transactionTypeCodes->map(function($e) {
                return $e->code;
            })->toArray();

            if (count($sysOfferTransCodes) > 0) {
                $startTime = "$year-$month-01 00:00:00";
                $endTime = date("Y-m-t", strtotime($startTime)) . ' 23:59:59';

                $results = self::createBaseQuery(
                    o2oDb: $o2oDb,
                    startTime: $startTime,
                    endTime: $endTime,
                    sysOfferTransCodes: $sysOfferTransCodes
                )->get();

                foreach ($results as $result) {
                    $date = $result->date;
                    echo "Processing {$transType->id} $date...";
                    $entry = ReportTransactionType::query()
                        ->where('transaction_type_id', $transType->id)
                        ->where('date', $date)->first();

                    if (!$entry) {
                        $entry = new ReportTransactionType();
                        $entry->transaction_type_id = $transType->id;
                        $entry->date = $date;
                        $entry->year = $year;
                        $entry->month = $month;
                    }

                    $entry->total_count = $result->total_count;
                    $entry->total_amount = $result->total_amount;
                    $entry->save();
                    echo "Processing $date...done\n";
                }

            }
        }

        return 0;
    }
}
