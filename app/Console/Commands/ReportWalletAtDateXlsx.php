<?php

namespace App\Console\Commands;

use App\Components\ReportExcel\ExcelModel\BlockIntegratedBank;
use App\Components\ReportExcel\ExcelModel\BlockTransactionDetail;
use App\Components\ReportExcel\ExcelReportBuilder;
use App\Helpers\ExcelBuilder;
use App\Helpers\PhpDoc;
use App\Models\TransactionTypeCode;
use App\Models\WalletTransaction;
use App\Services\XlsxService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
class ReportWalletAtDateXlsx extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ReportWalletAtDateXlsx {date}';

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
     * @throws \Exception
     */
    public function handle()
    {


        $date = $this->argument('date');
        if (date('Y-m-d',strtotime($date)) !== $date) {
            $this->error("Invalid date format Y-m-d");
            return;
        }

        $toExports = [
            'id' => 'ID',
            'code' => 'ACC_NO',
            'wallet_name' => 'NAME',
            'amount' => 'AMOUNT',
            'wallet_type_name' => 'TYPE'
        ];

        $ewalletDB = dbConnection('EWALLET');


        $processed = 0;
        $exportEntries = [];
        $output = storage_path("reports/report-$date.xlsx");

        if (is_file($output)) {
            unlink($output);
            $this->info("DELETED $output");
        }

        $ewalletDB->table('wallet_account')->selectRaw('id,code,wallet_name,amount')
            ->where('create_on', '<=', "$date 23:59:59")
            ->orderBy('create_on')
            ->chunk(10000, function ($entries) use($date, &$processed, &$exportEntries, $ewalletDB) {
                attachWalletTypeId($entries);

                foreach ($entries as $entry) {
                    $amountValue = getWalletBalanceAtDate($entry->id, $date, true);
                    $entry->amount_value = $amountValue;
                    $entry->amount = number_format($amountValue);
                    $entry->code = $entry->code. "\t";
                    if ($entry->wallet_type_id == 1) {
                        $exportEntries[] = (array)$entry;
                    }

                }

                $processed += count($entries);
                $this->info("Processing $processed");
            });

        usort($exportEntries, function($a, $b) {
            return $b['amount_value'] - $a['amount_value'];
        });

        XlsxService::exportZip($toExports, $exportEntries, $output);
    }
}
