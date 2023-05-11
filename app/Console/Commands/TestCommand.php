<?php

namespace App\Console\Commands;

use App\Components\ReportExcel\ExcelModel\BlockIntegratedBank;
use App\Components\ReportExcel\ExcelModel\BlockTransactionDetail;
use App\Components\ReportExcel\ExcelReportBuilder;
use App\Helpers\ExcelBuilder;
use App\Helpers\PhpDoc;
use App\Models\TransactionTypeCode;
use App\Services\XlsxService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
class TestCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test1';

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

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $file = 'D:\Projects\GroupVNPD\sbvreportcms\src\storage\cache.production\active-wallets\2022-09-30.json';
        dd(count(json_decode(file_get_contents($file))));

        $endTime = '2022-10-20 23:59:59';
        $walletDb = dbConnection(DB_CONNECTION_EWALLET);

        $total = 0;
        $countAll =     $walletDb->table('wallet_account')
            ->where('create_on', '<=', $endTime)->count();

        $walletDb->table('wallet_account')
            ->where('create_on', '<=', $endTime)
            ->orderBy('create_on')
            ->chunk(2000, function ($wallets) use(&$total) {
                $total += count($wallets);
            });

        dd($total, $countAll);
    }
}
