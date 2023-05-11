<?php

namespace App\Console\Commands;

use App\Models\CrossDb\CrossDbRelation;
use App\Models\CrossDb\RelationBelongsTo;
use App\Models\CrossDb\RelationHasMany;
use App\Models\ReportTopCustomer;
use App\Models\ReportTransactionType;
use App\Models\TransactionType;
use Illuminate\Console\Command;
use Illuminate\Database\ConnectionInterface;
use \Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class ReportTopCustomerProcess extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ReportTopCustomerProcess {fromDate} {--force}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';
    protected static RelationBelongsTo $customerRelation;
    protected static RelationBelongsTo $merchantContractSignRelation;
    protected static RelationHasMany $customerAccountRelation;


    public function handle()
    {
        self::initialRelations();
        $force = $this->option('force');

        $env = config_env('REPORT_ENV');
        $this->info("[$env]ReportTopCustomerProcess: force={$force}");


        $fromDate = $this->argument('fromDate');

        $customerCategories = [
            ['id' => 1, 'name' => 'Khách hàng cá nhân'],
            ['id' => 3, 'name' => 'Đơn vị chấp nhận thanh toán'],
        ];

        if ($force) {
            $this->info("TRUNCATE TABLE report_top_customers");
            DB::table('report_top_customers')->truncate();
            foreach ($customerCategories as $customerCategory) {
                $tableState = 'report_top_customers:' . $customerCategory['id'];
                $deleted = DB::table('report_process_states')
                    ->where('class', $tableState)
                    ->delete();
                $this->info("DELETED $deleted FROM report_process_states");
            }
        }


        foreach ($customerCategories as $customerCategory) {
            $tableState = 'report_top_customers:' . $customerCategory['id'];
            forEachMonth($tableState, function ($year, $moth) use($customerCategory) {
                $this->process($year, $moth, $customerCategory['id']);
            }, $fromDate);
        }

    }

    /**
     * Execute the console command.
     *
     * @return int
     * @throws \Exception
     */
    public function process($year, $month, $customerCategoryId)
    {

        $this->info("PROCESS $year-$month");
        $transTypes = TransactionType::query()->with('transactionTypeCodes')->get();



        $o2oDb = dbConnection('O2O');

        foreach ($transTypes as $transType) {
            $sysOfferTransCodes = $transType->transactionTypeCodes->map(function ($e) {
                return $e->code;
            })->toArray();

            if (count($sysOfferTransCodes) > 0) {
                $startTime = "$year-$month-01 00:00:00";
                $time = strtotime($startTime);
                $endTime = date("Y-m-t", $time) . ' 23:59:59';

                $startMonth = "$year-$month-01";
                $endMonth = date("Y-m-t", $time);

                $entries = self::queryTransData($o2oDb, $startTime, $endTime, $sysOfferTransCodes, $customerCategoryId);


                foreach ($entries as $r) {
                    $time = strtotime($r->TRANS_DATETIME);
                    $date = date('Y-m-d', $time);

                    echo "Processing {$transType->id} $date...";

                    $entry = ReportTopCustomer::query()
                        ->where('transaction_type_id', $transType->id)
                        ->where('customer_category_id', $customerCategoryId)
                        ->where('customer_id', $r->MAIN_CUSTOMER_ID)
                        ->where('year', $year)
                        ->where('month', $month)->first();

                    if (!$entry) {
                        $entry = new ReportTopCustomer();
                        $entry->transaction_type_id = $transType->id;
                        $entry->customer_category_id = $customerCategoryId;
                        $entry->year = $year;
                        $entry->month = $month;
                    }

                    if (!isset($r->CUSTOMER)) {
                        continue;
                    }

                    $CUSTOMER = $r->CUSTOMER;

                    $customerAccounts = $r->customerAccounts;
                    $entry->customer_id = $r->MAIN_CUSTOMER_ID;
                    $entry->customer_name = mb_strtoupper($CUSTOMER->CUSTOMER_NAME);
                    $entry->identity_number = $CUSTOMER->IDENTITY_NUMBER;
                    $entry->total_count = $r->total_count;
                    $entry->total_amount = $r->total_amount;
                    if (count($customerAccounts) > 0) {
                        $walletId = $customerAccounts[0]->WALLET_ID;
                        $entry->opening_balance = getWalletBalanceAtDate($walletId, $startMonth);
                        $entry->ending_balance = getWalletBalanceAtDate($walletId, $endMonth);
                    }
                    $entry->save();
                    echo "Processing $date...done\n";
                }


            }
        }

        return 0;
    }

    public static function createBaseQuery(ConnectionInterface $o2oDb,
                                                         $startTime,
                                                         $endTime,
                                                         $sysOfferTransCodes): Builder
    {
        $baseQuery = $o2oDb->table('ORDER_BASE_TRANS_M')
            ->selectRaw('IFNULL(DES_CUST_ID, SRC_CUST_ID) as MAIN_CUSTOMER_ID, TRANS_DATETIME, COUNT(*) `total_count`, SUM(TRANS_AMOUNT) `total_amount`')
            ->where('TRANS_STATUS', 1)
            ->where('TRANS_DATETIME', '>=', $startTime)
            ->where('TRANS_DATETIME', '<=', $endTime)
            ->where(function (Builder $query) {
                $query->where('SRC_ACCOUNT_TYPE', CUSTOMER_ACCOUNT_TYPE_POSTPAY)
                    ->orWhere('DES_ACCOUNT_TYPE', CUSTOMER_ACCOUNT_TYPE_POSTPAY);
            })
            ->whereIn('SYS_OFFER_TRANS_CODE', $sysOfferTransCodes);

            return $baseQuery;

    }

    private static function initialRelations()
    {
        self::$customerRelation = new RelationBelongsTo(
            table: 'CUSTOMER',
            alias: 'CUSTOMER',
            fields: ['CUSTOMER_ID', 'IDENTITY_NUMBER', 'CUSTOMER_NAME'],
            foreignKey: 'MAIN_CUSTOMER_ID',
            ownerKey: 'CUSTOMER_ID',
            connection: 'O2O'
        );


        self::$customerAccountRelation = new RelationHasMany(
            table: 'CUSTOMER_ACCOUNT',
            alias: 'customerAccounts',
            fields: ['ACCOUNT_ID', 'WALLET_ID', 'CUSTOMER_ID', 'ACCOUNT_NUMBER'],
            foreignKey: 'CUSTOMER_ID',
            localKey: 'MAIN_CUSTOMER_ID',
            connection: 'O2O',
            query: function (Builder $builder) {
                $builder->where('ACCOUNT_TYPE', 1);
            }
        );
    }

    /**
     * @throws \Exception
     */
    public static function queryTransData(ConnectionInterface $o2oDb,
                                          $startTime,
                                          $endTime,
                                          $sysOfferTransCodes,
                                          $customerCategoryId
    )
    {
        $baseQuery = self::createBaseQuery($o2oDb, $startTime, $endTime, $sysOfferTransCodes);

        $merchantContractSignCustomerIDs = $o2oDb->table('MERCHANT_CONTRACT_SIGN')
            ->selectRaw('CUSTOMER_ID')
            ->where('CONTRACT_CUS_SIGN_DATETIME', '<=', $endTime)
            ->where('CONTRACT_SIGN_STATUS', 1)
            ->get()
            ->pluck('CUSTOMER_ID')
            ->toArray();

        $baseQuery->where(function(Builder $builder) use($merchantContractSignCustomerIDs, $customerCategoryId) {
            if ($customerCategoryId === 3) {
                // Đơn vị chấp nhận thanh toán
                $builder->whereIn('DES_CUST_ID', $merchantContractSignCustomerIDs)
                    ->orWhereIn('SRC_CUST_ID', $merchantContractSignCustomerIDs);
            } else {
                // Ví cá nhân
               $builder->whereNotIn('DES_CUST_ID', $merchantContractSignCustomerIDs)
                    ->orWhereNotIn('SRC_CUST_ID', $merchantContractSignCustomerIDs);
            }
        });

        $baseQuery->limit(100)
            ->groupByRaw('MAIN_CUSTOMER_ID');

        $clone = $baseQuery->clone();

        $topAmountEntries = $baseQuery->orderBy('total_amount', 'desc')->get();
        $topCountEntries = $clone->orderBy('total_count', 'desc')->get();
        $entries = $topAmountEntries->merge($topCountEntries);

        CrossDbRelation::attachBelongsTo($entries, self::$customerRelation);
        CrossDbRelation::attachHasMany($entries, self::$customerAccountRelation);

        /*CrossDbRelation::attachBelongsTo($entries, new RelationBelongsTo(
            table: 'MERCHANT_CONTRACT_SIGN',
            alias: 'MerchantContractSign',
            fields: ['CUSTOMER_ID', 'CONTRACT_NO'],
            foreignKey: 'MAIN_CUSTOMER_ID',
            ownerKey: 'CUSTOMER_ID',
            connection: 'O2O',
            query: function (Builder $builder) use ($endTime) {
                $builder->where('CONTRACT_CUS_SIGN_DATETIME', '<=', $endTime)
                    ->where('CONTRACT_SIGN_STATUS', '=', 1);
            }
        ));*/

        return $entries;
    }
}
