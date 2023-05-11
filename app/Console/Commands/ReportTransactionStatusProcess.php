<?php

namespace App\Console\Commands;

use App\Models\ReportMbTransactionStatus;
use App\Models\ReportTransactionStatus;
use App\Models\ReportTransactionType;
use App\Models\TransactionType;
use Illuminate\Console\Command;
use \Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use \Illuminate\Database\ConnectionInterface;

class ReportTransactionStatusProcess extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ReportTransactionStatusProcess {fromDate} {--force}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    public function handle()
    {
        ini_set('memory_limit', '1G');
        $force = $this->option('force');
        $env = config_env('REPORT_ENV', 'local');

        $this->info("[$env]ReportTransactionStatusProcess");
        $accountTypes = [
            CUSTOMER_ACCOUNT_TYPE_MB => 'report_mb_transaction_statuses',
            CUSTOMER_ACCOUNT_TYPE_POSTPAY => 'report_transaction_statuses'
        ];


        if ($force) {
            $tables = ['report_mb_transaction_statuses', 'report_transaction_statuses'];
            foreach ($tables as $table) {
                $this->info("TRUNCATE TABLE $table");
            }
            $stateClass = $table;
            $deleted = DB::table('report_process_states')
                ->where('class', $stateClass)
                ->delete();
            $this->info("DELETED $deleted FROM report_process_states");

        }

        $fromDate = $this->argument('fromDate');


        foreach ($accountTypes as $accountType => $tableName) {
            forEachMonth($tableName, function($year, $month) use($accountType) {
                $walletTypeDvcnttId = 3;
                $walletTypeViCaNhanId = 1;

                if ($accountType === CUSTOMER_ACCOUNT_TYPE_POSTPAY) {
                    $this->process($year, $month, $accountType, $walletTypeDvcnttId);
                    $this->process($year, $month, $accountType, $walletTypeViCaNhanId);
                } else {
                    $this->process($year, $month, $accountType, $walletTypeViCaNhanId);
                }

            }, $fromDate);
        }
    }

    public static function createBaseQuery(ConnectionInterface $o2oDb, $accountType, $startTime, $endTime, $sysOfferTransCodes): Builder
    {
        return $o2oDb->table('ORDER_BASE_TRANS_M')
            ->selectRaw('TRANS_STATUS, COUNT(*) `total_count`, SUM(TRANS_AMOUNT) `total_amount`, DATE(TRANS_DATETIME) date')
            ->where('TRANS_DATETIME', '>=', $startTime)
            ->where('TRANS_DATETIME', '<=', $endTime)
            ->whereIn('SYS_OFFER_TRANS_CODE', $sysOfferTransCodes)
            ->where(function(Builder $query) use($accountType) {
                $query->where('SRC_ACCOUNT_TYPE', $accountType)
                    ->orWhere('DES_ACCOUNT_TYPE', $accountType);
            }) ->groupByRaw('DATE(TRANS_DATETIME), TRANS_STATUS');

    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function process($year, $month, $accountType, $walletTypeId)
    {
        $o2oDb = dbConnection('O2O');

        $transTypes = TransactionType::query()->with('transactionTypeCodes')->get();

        $isMB = $accountType === CUSTOMER_ACCOUNT_TYPE_MB;
        if ($isMB) {
            $model = ReportMbTransactionStatus::class;
        } else {
            $model= ReportTransactionStatus::class;
        }

        foreach ($transTypes as $transType) {
            $sysOfferTransCodes = $transType->transactionTypeCodes->map(function($e) {
                return $e->code;
            })->toArray();

            if (count($sysOfferTransCodes) > 0) {
                $startTime = "$year-$month-01 00:00:00";
                $endTime = date("Y-m-t", strtotime($startTime)) . ' 23:59:59';

                $baseQuery = self::createBaseQuery(
                    o2oDb: $o2oDb,
                    accountType: $accountType,
                    startTime: $startTime,
                    endTime: $endTime,
                    sysOfferTransCodes: $sysOfferTransCodes
                );

                if (!$isMB) {
                    $merchantContractSignCustomerIDs = $o2oDb->table('MERCHANT_CONTRACT_SIGN')
                        ->selectRaw('CUSTOMER_ID')
                        ->where('CONTRACT_CUS_SIGN_DATETIME', '<=', $endTime)
                        //->where('CONTRACT_SIGN_STATUS', 1)
                        ->get()
                        ->pluck('CUSTOMER_ID')
                        ->toArray();

                    $baseQuery->where(function(Builder $builder) use($merchantContractSignCustomerIDs, $walletTypeId) {
                        if ($walletTypeId === 3) {
                            // Đơn vị chấp nhận thanh toán
                            $builder->whereIn('DES_CUST_ID', $merchantContractSignCustomerIDs)
                                ->orWhereIn('SRC_CUST_ID', $merchantContractSignCustomerIDs);
                        } else {
                            // Ví cá nhân
                            $builder->whereNotIn('DES_CUST_ID', $merchantContractSignCustomerIDs)
                                ->orWhereNotIn('SRC_CUST_ID', $merchantContractSignCustomerIDs);
                        }
                    });
                }



                $results = $baseQuery->get();

                foreach ($results as $result) {
                    $date = $result->date;
                    $TRANS_STATUS = $result->TRANS_STATUS;
                    echo "Processing {$transType->id} $date...";
                    $entry = $model::query()
                        ->where('transaction_type_id', $transType->id)
                        ->where('transaction_status', $TRANS_STATUS)
                        ->where('wallet_type_id', $walletTypeId)
                        ->where('date', $date)->first();

                    if (!$entry) {
                        $entry = new $model();
                        $entry->transaction_type_id = $transType->id;
                        $entry->transaction_status = $TRANS_STATUS;
                        $entry->wallet_type_id = $walletTypeId;
                        $entry->date = $date;
                        $entry->year = $year;
                        $entry->month = $month;
                    }

                    $entry->total_count = $result->total_count;
                    $entry->total_amount = $result->total_amount;
                    $entry->save();
                    $accountTypeName = $accountType === CUSTOMER_ACCOUNT_TYPE_POSTPAY ? 'POSTPAY' : 'MB';
                    echo "Processing $accountTypeName $year:$month $date...done\n";
                }


            }
        }

        return 0;
    }
}
