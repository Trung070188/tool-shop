<?php

namespace App\Console\Commands;

use App\Models\ConnectionUnit;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ReportConnectionUnit extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ReportConnectionUnit';

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
        $this->initialData();
        $this->syncWithPartnerMerchant();
        return 0;
    }

    private function initialData()
    {
        DB::table('connection_units')->truncate();
        $this->info("TRUNCATE TABLE connection_units");
        $units = [
            [
                'unit_types' => ConnectionUnit::getAllUnitTypes(),
                'partner_code' => 'NAPAS',
                'partner_type' => ConnectionUnit::PARTNER_TYPE_PARTNER,
                'partner_name' => 'CTCP Thanh toán Quốc gia Việt Nam',
                'month' => '11',
                'year' => '2021',
                'status' => 1,
                'partner_address' => 'Tầng 16 - 17 - 18, Tòa nhà Pacific Place, 83B Lý Thường Kiệt, Hoàn Kiếm, Hà Nội'
            ],
            [
                'unit_types' => [ConnectionUnit::UNIT_TYPE_TRANSFER, ConnectionUnit::UNIT_TYPE_EWALLET],
                'partner_code' => 'BIDV',
                'partner_type' => ConnectionUnit::PARTNER_TYPE_PARTNER,
                'partner_name' => 'Ngân hàng Thương mại cổ phần Đầu tư và Phát triển Việt Nam',
                'month' => '10',
                'year' => '2022',
                'status' => 1,
                'partner_address' => 'Tháp BIDV, số 194 đường Trần Quang Khải, phường Lý Thái Tổ, quận Hoàn Kiếm, thành phố Hà Nội'
            ],
            [
                'unit_types' => [ConnectionUnit::UNIT_TYPE_TRANSFER, ConnectionUnit::UNIT_TYPE_EWALLET],
                'partner_code' => 'MB',
                'partner_type' => ConnectionUnit::PARTNER_TYPE_BANK,
                'partner_name' => 'Ngân hàng Quân đội',
                'month' => '11',
                'year' => '2021',
                'status' => 1,
                'partner_address' => 'MB Grand Tower, số 65 Lê Văn Lương, quận Cầu Giấy, Hà Nội'
            ]
        ];


        $now = date('Y-m-d H:i:s');
        $bulkData = [];
        foreach ($units as $unit) {
            $y = $unit['year'];
            $m = $unit['month'];
            $fromDate = date('Y-m-d', strtotime("$y-$m-01"));
            forEachMonth('ReportConnectionUnit', function($year, $month) use ($unit, $now, &$bulkData) {
                $unit['create_date'] = $now;
                $unit['created_at'] = $now;
                $unit['updated_at'] = $now;
                $unit['year'] = $year;
                $unit['month'] = $month;
                foreach ($unit['unit_types'] as $unitType) {
                    $unit['unit_type'] = $unitType;
                    unset($unit['unit_types']);
                    $bulkData[] = $unit;
                }
            }, $fromDate);

        }
        DB::table('connection_units')->insert($bulkData);
        $this->info("INSERTED " . count($bulkData) . ' entries');
    }

    private function syncWithPartnerMerchant() {
        $db = dbConnection('O2O');
        $merchants = $db->table('PARTNER_MERCHANT')->get();
        foreach ($merchants as $merchant) {
            echo "Processing " . $merchant->MERCHANT_NAME . "..";
            if (isset($merchant->CREATE_TIME)) {
                $time = strtotime($merchant->CREATE_TIME);
                $year = date("Y", $time);
                $month = date('m', $time);
                $now = date('Y-m-d H:i:s');
                $tag = 'ReportConnectionUnit:syncWithPartnerMerchant';
                $bulkData = [];
                $fromDate = date('Y-m-d', strtotime("$year-$month-01"));
                forEachMonth($tag, function($year, $month) use ($now, &$bulkData, $merchant) {
                    $bulkData[] = [
                        'unit_type' => ConnectionUnit::UNIT_TYPE_EWALLET,
                        'partner_type' => ConnectionUnit::PARTNER_TYPE_PARTNER,
                        'partner_name' => $merchant->MERCHANT_NAME,
                        'partner_code' => strtoupper($merchant->MERCHANT_CODE),
                        'status' => 1,
                        'year' => $year,
                        'month' => $month,
                        'create_date' => $now,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }, $fromDate);

                DB::table('connection_units')->insert($bulkData);
                echo ("INSERTED " . count($bulkData) . ' entries' . PHP_EOL);
            }


        }
    }

    private function syncWithPartnerBank() {
        $db = dbConnection('O2O');
        $partnerBanks = $db->table('PARTNER_BANK')->where('CN_NAPAS_STATUS', 0)->get();
        foreach ($partnerBanks as $partnerBank) {
            $unit = new ConnectionUnit();
            $time = strtotime($partnerBank->CREATE_TIME);
            $year = date("Y", $time);
            $month = date('m', $time);

            $unit->status = 1;
            $unit->unit_type = ConnectionUnit::UNIT_TYPE_TRANSFER;
            $unit->partner_type = ConnectionUnit::PARTNER_TYPE_BANK;
            $unit->partner_address = $addressMap[$partnerBank->BANK_CODE] ?? '';
            $unit->partner_name = $partnerBank->BANK_FULL_NAME;
            $unit->partner_code = $partnerBank->BANK_CODE;
            $unit->year = $year;
            $unit->month = $month;
            $unit->save();
        }
    }
}
