<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SecuredAccountImport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'SecuredAccountImport';

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


        ini_set('memory_limit', '1G');
        $accounts = [
            //[1, '22010002096868'],
            //[2, '22010008906868'],
            [3, '22010009626868'],
        ];
        DB::table('bank_raw_trans')->truncate();
        $this->info("TRUNCATE TABLE bank_raw_trans");

        foreach ($accounts as $t) {
            list($sheetIndex, $accountNumber) = $t;

            $input = storage_path("csv/EBK_BC_LICHSUGIAODICH_sheet$sheetIndex.csv");
            $this->process($accountNumber, $input);
        }


    }

    public function process($accountNumber, $filename) {
        $handle = fopen($filename, "r");

        $counter = 0;
        $bulkData = [];
        $totalInserted = 0;
        while (($line = fgets($handle)) !== false) {
            $counter++;

            if ($counter >= 14) {
                $line = trim($line);
                $csv = str_getcsv($line);

                list(, $date, , $amountDebit, $amountCredit, $balance, $desc) = $csv;
                $transDate = \DateTime::createFromFormat('d/m/Y H:i:s', $date )->format('Y-m-d H:i:s');
                $amountDebit = str_replace(',', '', $amountDebit);
                $amountCredit = str_replace(',', '', $amountCredit);
                $balance = str_replace(',', '', $balance);

                $row = [
                    'account_number' => $accountNumber,
                    'trans_date' => $transDate,
                    'amount_debit' => $amountDebit,
                    'amount_credit' => $amountCredit,
                    'balance' => $balance,
                    'description' => $desc,
                    'created_at' => date('Y-m-d H:i:s')
                ];
                $bulkData[] = $row;
                $countBulkData = count($bulkData);
                if ($countBulkData === 2000) {
                    DB::table('bank_raw_trans')->insert($bulkData);
                    $bulkData = [];
                    $totalInserted += $countBulkData;
                    $this->info("ACC $accountNumber: Inserted $countBulkData. Total: $totalInserted");
                }
            }

        }

        $countBulkData = count($bulkData);
        if ($countBulkData > 0) {
            DB::table('bank_raw_trans')->insert($bulkData);
            $totalInserted += $countBulkData;
            $this->info("Inserted $countBulkData. Total: $totalInserted");
        }

        fclose($handle);
    }
}
