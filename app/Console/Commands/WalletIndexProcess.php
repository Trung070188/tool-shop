<?php

namespace App\Console\Commands;

use App\Models\CrossDb\CrossDbRelation;
use App\Models\CrossDb\RelationBelongsTo;
use Illuminate\Console\Command;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class WalletIndexProcess extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'WalletIndexProcess {action} {fromDate}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    private string $dirCache;
    private string $dirWalletBalanceStorage;
    private string $dirActiveWalletStorage;
    private string $dirRecentActiveWalletStorage;
    private string $dirRecentActiveWalletStorageBak;

    /**
     * Execute the console command.
     *
     * @return int
     * @throws \Exception
     */
    public function handle()
    {
        ini_set('memory_limit', '2G');
        $action = $this->argument('action');
        $this->prepareDirs();
        if ($action == 'all') {
            $this->IndexWalletBalances();
            $this->IndexActiveWallets();
            $this->IndexRecentActiveWallets();
        } else {
            if (str_starts_with($action, 'Index')) {
                return $this->$action();
            }
        }
        return 0;
    }

    private function prepareDirs()
    {
        $env = config_env('REPORT_ENV');
        $cacheDirName = 'cache.' . $env;
        $this->dirCache = storage_path($cacheDirName);
        $this->dirWalletBalanceStorage = storage_path($cacheDirName . '/wallet-balances');
        $this->dirActiveWalletStorage = storage_path("$cacheDirName/active-wallets");
        $this->dirRecentActiveWalletStorage = storage_path("$cacheDirName/recent-wallets");
        $this->dirRecentActiveWalletStorageBak = storage_path("$cacheDirName/recent-wallets.bak");
        $dirs = [
            $this->dirCache,
            $this->dirWalletBalanceStorage,
            $this->dirActiveWalletStorage,
            $this->dirRecentActiveWalletStorage
        ];
        foreach ($dirs as $dir) {
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
        }
    }

    public static function getRelationCustomerBankLink($endTime): RelationBelongsTo
    {
        return new RelationBelongsTo(
            table: 'CUSTOMER_BANK_LINK',
            alias: 'customerBankLink',
            #fields: ['*'],
            fields: ['BANK_LINK_ID', 'ACCOUNT_NUMBER', 'ACCOUNT_ID', 'LINKED_STATUS'],
            foreignKey: 'code',
            ownerKey: 'ACCOUNT_NUMBER',
            connection: 'O2O',
            query: function (Builder $builder) use($endTime) {
                $builder->whereRaw('(UNLINKED_DATETIME IS NULL OR UNLINKED_DATETIME >?)', [$endTime]);
            }
        );
    }

    /**
     * @return int
     * @throws \Exception
     * @example
     * php artisan ReportWalletStatusProcess IndexWalletBalances 2022-11-30
     * php artisan ReportWalletStatusProcess IndexWalletBalances auto
     */
    public function IndexWalletBalances()
    {
        //$this->truncate('wallet_balances_at_dates');

        $fromDate = $this->argument('fromDate');


        forEachDate("IndexWalletBalances", function ($date) {
            $this->indexWalletBalanceByDate($date);
        }, $fromDate);
        return 0;
    }

    public function IndexRecentActiveWallets()
    {
        $db = dbConnection('EWALLET');
        //$fromDate = $this->argument('fromDate');
        // Ngày gần nhất có data là 2022-02-21
        $fromDate = '2022-02-21';
        $endTime = "$fromDate 23:59:59";

        $bankLinkRelation = new RelationBelongsTo(
            table: 'O2O_CUSTOMER_BANK_LINK',
            alias: 'customerBankLink',
            fields: ['*'],
            foreignKey: 'code',
            ownerKey: 'ACCOUNT_NUMBER',
            connection: 'O2O',
            query: function (Builder $builder) use($endTime) {
                $builder->whereRaw('(UNLINKED_DATETIME IS NULL OR UNLINKED_DATETIME >?)', [$endTime]);
            }
        );

        forEachDate("IndexRecentActiveWallets", function ($date, $startTime, $endTime) use ($db, $bankLinkRelation) {
            echo "[RecentActiveWallets] Processing $date...";

            $filename = $this->dirRecentActiveWalletStorage . '/' . $date . '.json';

            if (is_file($filename)) {
                $this->info("Filename $filename already exists");
                return;
            }
            $saveData = self::getRecentActiveWallet($date);

            $countSaveData = count($saveData);

            if ($countSaveData > 0) {
                file_put_contents($filename, json_encode($saveData));
                $this->info("Saved $countSaveData");
            } else {
                $this->warn("Nothing to saved");
            }
        }, $fromDate);

        return 0;
    }

    public static function getActiveWallet($date, $verbose = true): array
    {
        $endTime = "$date 23:59:59";

        $ewalletDb = dbConnection('EWALLET');
        $walletIds = [];
        $countOriginal =  $ewalletDb->table('wallet_account')
            ->where('create_on', '<=', $endTime)
            ->count();
        $totalWallet = 0;
        $ewalletDb->table('wallet_account')
            ->selectRaw('id,wallet_name,code')
            ->where('create_on', '<=', $endTime)
            ->orderBy('create_on')
            ->chunk(2000, function ($wallets) use ($verbose, &$totalWallet, $date, $countOriginal,
                &$walletIds, $endTime) {
                $totalWallet += count($wallets);
                $percent = get_percent($totalWallet, $countOriginal);

                if ($verbose) {
                    echo ("[GetActiveWallet::$date] -> [$percent%] $totalWallet/$countOriginal\n");
                }

                CrossDbRelation::attachBelongsTo($wallets, WalletIndexProcess::getRelationCustomerBankLink($endTime));

                foreach ($wallets as $wallet) {
                    if ($wallet->customerBankLink) {
                        $walletIds[] = $wallet->id;
                    }
                }
            });

        return $walletIds;
    }

    public static function getRecentActiveWallet($date, $verbose = true): array
    {
        $returnWalletIds = [];
        $ewalletDb = dbConnection('EWALLET');
        $endTime = "$date 23:59:59";
        $previousTime = date('Y-m-d H:i:s', strtotime('-12 months', strtotime($endTime)));

        $countOriginal =  $ewalletDb->table('wallet_account')
            ->selectRaw('id,wallet_name,code')
            ->where('create_on', '<=', $endTime)->count();

        $ewalletDb->table('wallet_account')
            ->selectRaw('id,wallet_name,code')
            ->where('create_on', '<=', $endTime)
            ->orderBy('create_on')
            ->chunk(2000, function ($wallets) use ($verbose, &$returnWalletIds, &$totalWallet, $ewalletDb, $endTime,
                $previousTime, $countOriginal, $date) {
                $totalWallet += count($wallets);
                $percent = get_percent($totalWallet, $countOriginal);
                if ($verbose) {
                    echo ("[GetRecentActiveWallet::$date] -> [$percent%]\n");
                }

                CrossDbRelation::attachBelongsTo($wallets,  WalletIndexProcess::getRelationCustomerBankLink($endTime));

                $walletIds = [];
                foreach ($wallets as $wallet) {

                    if ($wallet->customerBankLink) {
                        $walletIds[] = $wallet->id;
                    }
                }

                $transMapAll = [];

                $receiverTrans = $ewalletDb->table('wallet_transaction')
                    ->selectRaw('receiver_wallet, count(*) count')
                    ->whereIn('receiver_wallet', $walletIds)
                    ->where('create_on', '>=', $previousTime)
                    ->where('create_on', '<=', $endTime)
                    ->groupBy('receiver_wallet')
                    ->get();

                $senderTrans = $ewalletDb->table('wallet_transaction')
                    ->selectRaw('sender_wallet, count(*) count')
                    ->whereIn('sender_wallet', $walletIds)
                    ->where('create_on', '>=', $previousTime)
                    ->where('create_on', '<=', $endTime)
                    ->groupBy('sender_wallet')
                    ->get();

                foreach ($receiverTrans as $trans) {
                    $transMapAll[$trans->receiver_wallet] = $trans->count;
                }

                foreach ($senderTrans as $trans) {
                    if (!isset($transMapAll[$trans->sender_wallet])) {
                        $transMapAll[$trans->sender_wallet] = 0;
                    }
                    $transMapAll[$trans->sender_wallet] += $trans->count;
                }


                foreach ($wallets as $wallet) {
                    if (isset($transMapAll[$wallet->id])) {
                        $returnWalletIds[] = $wallet->id;
                    }
                }
            });

        return $returnWalletIds;
    }

    public function IndexActiveWallets()
    {
        // Ngày gần nhất có data là 2021-11-30
        $fromDate = '2021-11-29';


        forEachDate("IndexActiveWallets", function ($date, $startTime, $endTime) {
            echo "[IndexActiveWallets]Processing $date...";
            $filename = $this->dirActiveWalletStorage . '/' . $date . '.json';
            if (is_file($filename)) {
                $this->info("Filename $filename already exists");
                return;
            }

            $saveData = self::getActiveWallet($date);

            if (count($saveData) > 0) {
                file_put_contents($filename, json_encode($saveData));
                $this->info("Saved " . count($saveData));
            } else {
                $this->warn("Nothing to saved");
            }

        }, $fromDate);

        return 0;
    }

    private function getLatestWalletTransactionID($wallets, $endTime): array
    {
        $walletIDs = [];
        foreach ($wallets as $wallet) {
            $walletIDs[] = $wallet->id;
        }

        $db = dbConnection('EWALLET');
        $walletDict = [];
        $receiverWalletTransactions = $db->table('wallet_transaction')
            ->selectRaw('
            receiver_wallet as wallet_id,
            (array_agg(receiver_balance ORDER BY create_on DESC))[1] agg_wallet_balance,
            (array_agg(id ORDER BY create_on DESC))[1] as agg_id,
            (array_agg(create_on ORDER BY create_on DESC))[1] as agg_create_on
            ')
            ->where('create_on', '<=', $endTime)
            ->whereIn('receiver_wallet', $walletIDs)
            ->groupBy('receiver_wallet')
            ->get();

        $senderWalletTransactions = $db->table('wallet_transaction')
            ->selectRaw('
                   sender_wallet as wallet_id,
            (array_agg(sender_balance ORDER BY create_on DESC))[1] agg_wallet_balance,
            (array_agg(id ORDER BY create_on DESC))[1] as agg_id,
            (array_agg(create_on ORDER BY create_on DESC))[1] as agg_create_on
            ')
            ->where('create_on', '<=', $endTime)
            ->whereIn('sender_wallet', $walletIDs)
            ->groupBy('sender_wallet')
            ->get();

        #$walletTransactionList = [];
        foreach ($receiverWalletTransactions as $t) {
            $walletDict[$t->wallet_id][] = (array)$t;
        }

        foreach ($senderWalletTransactions as $t) {
            $walletDict[$t->wallet_id][] = (array)$t;
        }

        #$testWalletId = '7f6797d1-cd10-435a-86cf-448b995c83ca';

        $result = [];
        foreach ($walletDict as $walletId => $groups) {
            if (count($groups) > 1) {
                usort($groups, function ($a, $b) {
                    return strtotime($b['agg_create_on']) - strtotime($a['agg_create_on']);
                });
                $walletDict[$walletId] = $groups;
            }
        }

        foreach ($walletDict as $walletId => $group) {
            $result[$walletId] = $group[0]['agg_wallet_balance'];
        }

        return $result;
    }

    /**
     * @param $date
     * @return int
     * @throws \Exception
     * @example php artisan ReportWalletStatusProcess IndexWalletBalances 2022-11-30
     */
    public function indexWalletBalanceByDate($date): int
    {

        $endTime = $date . ' 23:59:59';
        $startTime = $date . ' 00:00:00';
        $db = dbConnection('EWALLET');

        //$date = '2022-09-09';
        $time = strtotime($date);
        $currentFileCache = $this->dirWalletBalanceStorage . "/" . $date . '.json';

        if (is_file($currentFileCache)) {
            $this->warn("Cache file: $currentFileCache already exists");
            return 0;
        }

        $groupByWallets = [];
        $processed = 0;
        $total = $db->table('wallet_account')->selectRaw('id,code,wallet_name,amount')
            ->where('create_on', '<=', "$date 23:59:59")
            ->count();

        $db->table('wallet_account')->selectRaw('id,code,wallet_name,amount')
            ->where('create_on', '<=', "$date 23:59:59")
            ->orderBy('create_on', 'asc')
            ->chunk(1000, function ($wallets) use ($db, $endTime, &$processed, &$groupByWallets, $total, $date) {

                $lastWalletBalances = $this->getLatestWalletTransactionID($wallets, $endTime);

                foreach ($lastWalletBalances as $walletId => $balance) {
                    if ($balance > 0) {
                        $groupByWallets[$walletId] = $balance;
                    }
                }
                $processed += count($wallets);
                $p = get_percent($processed, $total);
                echo "$date -> [$p %]Processing $processed/$total\n";
            });

        file_put_contents($currentFileCache, json_encode($groupByWallets));


        echo "DONE -> $processed \n";

        return 0;
    }
}
