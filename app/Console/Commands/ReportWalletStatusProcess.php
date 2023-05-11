<?php

namespace App\Console\Commands;


use App\Models\ReportWalletStatus;
use Illuminate\Console\Command;

class ReportWalletStatusProcess extends Command
{

    /**
     * The name and signature of the console command.
     * @example php artiasn ReportWalletStatusProcess IndexWalletBalances
     * @var string
     */
    protected $signature = 'ReportWalletStatusProcess {action} {fromDate} {--force} ';
    private array $businessWalletMap = [];
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

    public function handle()
    {
        $env = config_env('REPORT_ENV');
        $cacheDirName = 'cache.' . $env;
        $this->dirCache = storage_path($cacheDirName);
        $this->dirWalletBalanceStorage = storage_path($cacheDirName. '/wallet-balances');
        $this->dirActiveWalletStorage = storage_path("$cacheDirName/active-wallets");
        $this->dirRecentActiveWalletStorage = storage_path("$cacheDirName/recent-wallets");

        $force = $this->option('force');
        $env = config_env('REPORT_ENV', 'local');
        $this->info("[$env]ReportWalletStatusProcess");
        $action = $this->argument('action');
        ini_set('memory_limit', '2G');
        if ($action === 'MainProcess') {
            return $this->actionMainProcess();
        } else if ($action === 'IndexAll') {
            return $this->actionIndexAll();
        }

        $this->warn("Action $action invalid");
    }



    public function actionTest() {

        $fromDate = $this->argument('fromDate');

        $class = static::class;
        forEachDate($class . '::_thongKeViDaPhatHanh', function($a, $b, $c) {
            $this->_thongKeViDaPhatHanh($a, $b, $c);
        }, $fromDate);
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function actionMainProcess()
    {

        $fromDate = $this->argument('fromDate');

        if ($this->option('force')) {
            dbTableTruncate('report_wallet_statuses');
        }

        $class = static::class;
        forEachDate($class . '::_thongKeViDaPhatHanh', function($a, $b, $c) {
            $this->_thongKeViDaPhatHanh($a, $b, $c);
        }, $fromDate);

        forEachDate($class . '::_thongKeViDaKichHoat', function($a, $b, $c) {
            $this->_thongKeViDaKichHoat($a, $b, $c);
        }, $fromDate);

        forEachDate($class . '::_thongKeViDangHoatDong', function($a, $b, $c) {
            $this->_thongKeViDangHoatDong($a, $b, $c);
        }, $fromDate);

        return 0;
    }

    private function _thongKe($walletStatus, $date, $startTime, $endTime) {
        list($y,$m,$d) = explode('-', $date);


        echo "Processing $date...\n";
        if ($walletStatus == ReportWalletStatus::WALLET_STATUS_ALL) {
            $ewalletDb = dbConnection('EWALLET');
            $wallets = $ewalletDb->table('wallet_account')
                ->selectRaw('id,code,wallet_name, amount')
                ->where('create_on', '<=', $endTime)
                ->get();

        } else if ($walletStatus == ReportWalletStatus::WALLET_STATUS_ACTIVE) {
            $filename = $this->dirActiveWalletStorage . '/' . $date . '.json';
            if (!is_file($filename)) {
                echo "Nothing to process\n";
                return;
            }

            $contents = json_decode(file_get_contents($filename), true);
            $wallets = array_map(function($walletId) {
                return (object)[
                    'id' => $walletId,
                    'amount' => 0,
                ];
            }, $contents);

        } else if ($walletStatus == ReportWalletStatus::WALLET_STATUS_RECENT_ACTIVE) {
            $filename = $this->dirRecentActiveWalletStorage . '/' . $date . '.json';
            if (!is_file($filename)) {
                echo "Nothing to process -> $filename\n";
                return;
            }

            $contents = json_decode(file_get_contents($filename), true);
            $wallets = array_map(function($walletId) {
                return (object)[
                    'id' => $walletId,
                    'amount' => 0,
                ];
            }, $contents);
        } else {
            echo "Nothing to process2\n";
            return;
        }

        $this->attachBalanceAtDate($wallets, $date);


        $walletTypeGroups = [];
        foreach ($wallets as $wallet) {
            $walletTypeGroups[$wallet->wallet_type_id][] = $wallet;
        }

        foreach ($walletTypeGroups as $walletTypeId => $_wallets) {
            echo "Processing $date $walletTypeId $walletStatus...";
            $totalCount = count($_wallets);
            $totalAmount = 0;
            foreach ($_wallets as $wallet) {
                $totalAmount += $wallet->balance_at_date;
            }

            $report = ReportWalletStatus::query()
                ->where('wallet_type_id', $walletTypeId)
                ->where('date', $date)
                ->where('wallet_status', $walletStatus)
                ->first();

            if (!$report) {
                $report = new ReportWalletStatus();
                $report->date = $date;
                $report->wallet_type_id = $walletTypeId;
                $report->year = (int) $y;
                $report->month = (int) $m;
            }

            $report->total_amount = $totalAmount;
            $report->total_count = $totalCount;
            $report->wallet_status = $walletStatus;
            $report->save();

            echo "DONE -> ($totalCount, $totalAmount) \n";

        }
    }

    private function _thongKeViDaPhatHanh($date, $startTime, $endTime) {
        $this->_thongKe(ReportWalletStatus::WALLET_STATUS_ALL, $date, $startTime, $endTime);
    }

    private function _thongKeViDangHoatDong($date, $startTime, $endTime) {
        $this->_thongKe(ReportWalletStatus::WALLET_STATUS_RECENT_ACTIVE, $date, $startTime, $endTime);
    }

    private function _thongKeViDaKichHoat($date, $startTime, $endTime) {
        $this->_thongKe(ReportWalletStatus::WALLET_STATUS_ACTIVE, $date, $startTime, $endTime);
    }

    private function attachBalanceAtDate($wallets, $date): void
    {
        attachWalletTypeId($wallets, $date);

        foreach ($wallets as $wallet) {
            $wallet->balance_at_date = getWalletBalanceAtDate($wallet->id, $date);
            $wallet->formated_balance_at_date = number_format($wallet->balance_at_date);
            $wallet->formated_amount = number_format($wallet->amount);
        }

    }

}
