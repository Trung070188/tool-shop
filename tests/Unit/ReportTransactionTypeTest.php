<?php
namespace Tests\Unit;
use App\Console\Commands\ReportTransactionTypesProcess;
use App\Models\TransactionTypeCode;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ReportTransactionTypeTest extends TestCase
{
    public function testReportTransType() {
        $this->assertEquals("production", config_env('REPORT_ENV'));

        $year = 2022;
        $month = 7;
        $transactionTypeId = 1;
        $sysOfferTransCode = TransactionTypeCode::getSysOfferTransCodes($transactionTypeId);
        list($startTime, $endTime, $date) = get_time_range_from_date($year, $month, 1);

        $o2oDb = dbConnection('O2O');
        $liveResult = ReportTransactionTypesProcess::createBaseQuery($o2oDb, $startTime, $endTime, $sysOfferTransCode)
            ->first();

        $reportResult = DB::table('report_transaction_types')
            ->where('transaction_type_id', $transactionTypeId)
            ->where('date', $date)
            ->get()->first();


        $this->assertEquals(1055000, $liveResult->total_amount);
        $this->assertEquals(9, $liveResult->total_count);
        $this->assertEquals(1055000, $reportResult->total_amount);
        $this->assertEquals(9, $reportResult->total_count);

    }
}
