<?php

namespace App\Console\Commands;

use App\Models\CrossDb\CrossDbRelation;
use App\Models\CrossDb\RelationBelongsTo;
use App\Services\XlsxService;
use Illuminate\Console\Command;
use Illuminate\Database\Query\Builder;

class ExportCustomerProcess extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ExportCustomerProcess';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    private int $processed = 0;
    private array $districtMap = [];
    private array $provinceMap = [];

    /**
     * Execute the console command.
     *
     * @return int
     * @throws \Exception
     */
    public function handle()
    {
        $db = dbConnection(DB_CONNECTION_O2O);
        $this->districtMap = $db->table('z_district_temp')
            ->get()->pluck('title', 'code')->toArray();
        $this->provinceMap = $db->table('z_province_temp')
            ->get()->pluck('title', 'code')->toArray();


        $toExports = [
            'CUSTOMER_ID',
            'CUSTOMER_NAME',
            'IDD_NO',
            'IDD_PROVINCE',
            'IDD_DISTRICT',
            'PROVINCE_NAME',
            'DISTRICT_NAME',
        ];
        $endTime = '2021-12-31 23:59:59';
        $output = storage_path("reports/customers-2021.xlsx");
        $relationCustomerIdVerify = new RelationBelongsTo(
            table: 'CUSTOMER_ID_VERIFY',
            alias: 'CUSTOMER_ID_VERIFY',
            fields: ['VERIFY_ID','IDD_NO', 'CUSTOMER_ID', 'IDD_PROVINCE', 'IDD_DISTRICT'],
            foreignKey: 'CUSTOMER_ID',
            ownerKey: 'CUSTOMER_ID',
            connection: 'O2O'
        );


        $this->processed = 0;
        XlsxService::exportChunkZip($toExports, $output, function($callback) use($db, $endTime, $relationCustomerIdVerify) {
            $db->table('CUSTOMER')
                ->selectRaw('CUSTOMER_ID, CUSTOMER_NAME, IDENTITY_NUMBER')
                ->where('CREATE_DATE', '<=', $endTime)
                ->orderBy('CUSTOMER_ID')
                ->chunk(1000, function($entries) use($db, $callback, $relationCustomerIdVerify) {
                    $customerIDs = $entries->pluck('CUSTOMER_ID')->toArray();
                    $baseTransMap = $db->table('ORDER_BASE_TRANS_M')
                        ->selectRaw('M_BASE_TRANS_ID, IFNULL(DES_CUST_ID, SRC_CUST_ID) as MAIN_CUSTOMER_ID')
                        ->where(function(Builder $builder) use($customerIDs) {
                            $builder->whereIn('SRC_CUST_ID', $customerIDs)
                                ->orWhereIn('DES_CUST_ID', $customerIDs);
                        })->groupBy('MAIN_CUSTOMER_ID')
                        ->get()
                        ->pluck('M_BASE_TRANS_ID', 'MAIN_CUSTOMER_ID')
                    ->toArray();


                    CrossDbRelation::attachBelongsTo($entries, $relationCustomerIdVerify);

                    $exportEntries = [];
                    foreach ($entries as $entry) {
                        if (isset($baseTransMap[$entry->CUSTOMER_ID])) {

                            if (isset($entry->CUSTOMER_ID_VERIFY)) {
                                $civ = $entry->CUSTOMER_ID_VERIFY;
                                $entry->IDD_NO = $civ->IDD_NO;
                                $entry->IDD_PROVINCE = $civ->IDD_PROVINCE;
                                $entry->IDD_DISTRICT = $civ->IDD_DISTRICT;
                                $entry->PROVINCE_NAME = $this->provinceMap[$civ->IDD_PROVINCE] ?? '';
                                $entry->DISTRICT_NAME = $this->districtMap[$civ->IDD_DISTRICT] ?? '';
                            }

                            $exportEntries[] = (array)$entry;
                        }

                    }
                    $this->processed += count($entries);
                    $callback($exportEntries);
                    $this->info("Processing " . $this->processed);
                });
        });


     /*   $customers = $o2oDB->select($sql, [$endTime, $limit, $offset]);
        dd($customers);*/

        return 0;
    }

















}
