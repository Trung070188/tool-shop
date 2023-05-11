<?php

namespace App\Console\Commands;

use App\Models\SecuredAccountModel;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ReportSecuredAccount extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ReportSecuredAccount {fromDate}';

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
        #$o2o = dbConnection('O2O');
        $fromDate = $this->argument('fromDate');

        #$transTableName = 'secured_accounts_transactions';



        $this->reportTransactions();

        #$this->reportBalances();
        /*$accounts = [
            '22010002096868',
            '22010008906868',
            '22010009626868'
        ];
        $accountBulkData = [];
        foreach ($accounts as $account) {
            $accountBulkData[] = [
                'bank_code' => 'BIDV',
                'bank_name' => 'Ngân hàng Thương mại cổ phần Đầu tư và Phát triển Việt Nam',
            ];
        }*/

        return 0;
    }

    private function reportBalances() {
        $tableName = 'secured_accounts_balances';
        dbTableTruncate($tableName);
        $fromDate = $this->argument('fromDate');
        /**
         * @var SecuredAccountModel[] $accounts
         */
        $accounts = SecuredAccountModel::query()->get();
        foreach ($accounts as $account) {
            $total = 0;
            forEachMonth('ReportSecuredAccount::reportTransactions:'.$account->account_number, function($year, $month) use($tableName, $account, &$total) {
                $accNo = $account->account_number;

                list($start, $end) = get_time_range_from_month($year, $month);
                echo "Processing $accNo: $year-$month -> ";
                $transactions = DB::table('bank_raw_trans')
                    ->selectRaw('account_number, DATE(trans_date) transDate, GROUP_CONCAT(cal_balance ORDER BY id desc) calBalances')
                   ->where('account_number', $account->account_number)
                    ->where('trans_date', '>=', $start)
                    ->where('trans_date', '<=', $end)
                    ->groupByRaw('DATE(trans_date)')
                    ->orderBy('trans_date', 'asc')
                    ->get();
                if ($transactions->count() >0) {
                    $bulkData = [];
                    foreach ($transactions as $trans) {
                        $calBalances = explode(',', $trans->calBalances);
                        echo "Processing $accNo: $year-$month -> " . $trans->transDate. "\n";
                        $row = [
                            'account_number' => $account->account_number,
                            'balance' => (int) $calBalances[0],
                            'date' => $trans->transDate,
                            'month' => $month,
                            'year' => $year,
                            'created_at' => now(),
                            'updated_at' => now()
                        ];
                        $bulkData[] = $row;
                    }
                    $countBulkData = count($bulkData);
                    $total +=  $countBulkData;
                    echo "Inserted $countBulkData. Total $total\n";

                    DB::table($tableName)->insert($bulkData);
                }


            }, $fromDate);
        }


    }

    private function reCalculateBalances()
    {
        $transTableName = 'secured_accounts_transactions';
        $fromDate = $this->argument('fromDate');
        $accounts = [
            '22010002096868',
            '22010008906868',
            '22010009626868'
        ];

        foreach ($accounts as $accountNo) {
            $balance = 0;
            $total = DB::table('bank_raw_trans')->where('account_number', $accountNo)->count();
            $processed = 0;
            DB::table('bank_raw_trans')->where('account_number', $accountNo)
                ->orderBy('id')
                ->chunk(1000, function($transactions) use(&$balance, $total, &$processed, $accountNo) {
                    foreach ($transactions as $trans) {
                        if ($trans->amount_debit > 0) {
                            $balance -= $trans->amount_debit;
                        } else if ($trans->amount_credit > 0) {
                            $balance += $trans->amount_credit;
                        }

                        $processed++;
                        $percent = get_percent($processed, $total);
                        DB::table('bank_raw_trans')
                            ->where('id', $trans->id)
                            ->update(['cal_balance' => $balance]);
                        $this->info("ACC: $accountNo . " . $percent . '%');
                    }
                });

        }

        /*forEachMonth('ReportSecuredAccount', function($year, $month) use($transTableName) {
            list($start, $end) = get_time_range_from_month($year, $month);
            foreach (['D', 'C'] as $accountingType) {
                echo "Processing $accountingType: $year/$month...";
                $queryField = $accountingType === 'D' ? 'amount_debit' : 'amount_credit';
                $transactions = DB::table('bank_raw_trans')
                    ->selectRaw("
                    account_number accountNo,
                    DATE(trans_date) transDate,
                SUM(`$queryField`) totalAmount,
                COUNT(*) totalCount
                    ")
                    ->where('trans_date', '>=', $start)
                    ->where('trans_date', '<=', $end)
                    ->where($queryField, '>', 0)
                    ->groupBy('account_number')
                    ->groupByRaw('DATE(trans_date)')
                    ->orderBy('trans_date', 'asc')
                    ->get();
                if ($transactions->count() > 0) {
                    $bulkData = [];
                    foreach ($transactions as $trans) {
                        $now = date('Y-m-d H:i:s');
                        $bulkData[] = [
                            'account_number' => $trans->accountNo,
                            'accounting_type' => $accountingType,
                            'total_amount' => $trans->totalAmount,
                            'total_count' => $trans->totalCount,
                            'date' => $trans->transDate,
                            'year' => $year,
                            'month' => $month,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    }

                    DB::table($transTableName)->insert($bulkData);
                }

                echo "DONE\n";
            }


        }, $fromDate);*/
    }

    private function reportTransactions()
    {
        $transTableName = 'secured_accounts_transactions';
        dbTableTruncate($transTableName);
        $fromDate = $this->argument('fromDate');
        forEachMonth('ReportSecuredAccount::reportTransactions', function($year, $month) use($transTableName) {
            list($start, $end) = get_time_range_from_month($year, $month);
            foreach (['D', 'C'] as $accountingType) {
                echo "Processing $accountingType: $year/$month...";
                $queryField = $accountingType === 'D' ? 'amount_debit' : 'amount_credit';
                $transactions = DB::table('bank_raw_trans')
                    ->selectRaw("
                    account_number accountNo,
                    DATE(trans_date) transDate,
                SUM(`$queryField`) totalAmount,
                COUNT(*) totalCount
                    ")
                    ->where('trans_date', '>=', $start)
                    ->where('trans_date', '<=', $end)
                    ->where($queryField, '>', 0)
                    ->groupBy('account_number')
                    ->groupByRaw('DATE(trans_date)')
                    ->orderBy('trans_date', 'asc')
                    ->get();
                if ($transactions->count() > 0) {
                    $bulkData = [];
                    foreach ($transactions as $trans) {
                        $now = date('Y-m-d H:i:s');
                        $bulkData[] = [
                            'account_number' => $trans->accountNo,
                            'accounting_type' => $accountingType,
                            'total_amount' => $trans->totalAmount,
                            'total_count' => $trans->totalCount,
                            'date' => $trans->transDate,
                            'year' => $year,
                            'month' => $month,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    }

                    DB::table($transTableName)->insert($bulkData);
                }

                echo "DONE\n";
            }


        }, $fromDate);
    }

}
