<?php

namespace Tests\Unit;


use App\Console\Commands\ReportTopCustomerProcess;
use App\Models\TransactionTypeCode;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ReportTopCustomerTest extends TestCase
{
    /**
     * @group SbvReport
     * @throws \Exception
     */
    public function testCheckTopCustomerData() {

        $this->assertEquals("production",config_env('REPORT_ENV'));
        $o2oDb = dbConnection('O2O');
        $year = 2022;
        $month = 7;
        $customerId = 4802; # CHU MANH HUNG
        list($startTime, $endTime) = get_time_range_from_month($year, $month);

        $transactionTypeId = 1;
        $sysOfferTransCodes = TransactionTypeCode::getSysOfferTransCodes($transactionTypeId);

        $baseQuery = ReportTopCustomerProcess::createBaseQuery(
            o2oDb: $o2oDb,
            startTime: $startTime,
            endTime: $endTime,
            sysOfferTransCodes: $sysOfferTransCodes,
        );

        $liveResult = $baseQuery->whereRaw('(DES_CUST_ID=? OR SRC_CUST_ID=?)', [$customerId, $customerId])->first();

        $this->assertEquals(359228000, (int) $liveResult->total_amount);

        $reportResult = DB::selectOne('SELECT * FROM report_top_customers
         WHERE transaction_type_id=? AND `year`=? AND `month`=? AND customer_id=?', [
            $transactionTypeId, $year, $month, $customerId
        ]);
        

        $this->assertEquals(359228000, (int) $reportResult->total_amount);

    }
}
