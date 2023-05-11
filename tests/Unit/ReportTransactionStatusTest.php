<?php
namespace Tests\Unit;


use App\Console\Commands\ReportTransactionStatusProcess;
use App\Models\TransactionType;
use App\Models\TransactionTypeCode;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ReportTransactionStatusTest extends TestCase
{
    /**
     * @group SbvReport
     * @throws \Exception
     */
    public function testReportTransactionStatus() {

        $this->assertEquals("production", config_env('REPORT_ENV'));
        $year = 2022;
        $month = 7;
        list($startTime, $endTime, $date) = get_time_range_from_date($year, $month, 1);

        $accountType = CUSTOMER_ACCOUNT_TYPE_POSTPAY;
        $o2oDb = dbConnection('O2O');
        $transactionTypeId = 1;
        $sysOfferTransCodes = TransactionTypeCode::getSysOfferTransCodes($transactionTypeId);

        $liveResult = ReportTransactionStatusProcess::createBaseQuery(
            o2oDb: $o2oDb,
            accountType: $accountType,
            startTime: $startTime,
            endTime: $endTime,
            sysOfferTransCodes: $sysOfferTransCodes
        )->get()->pluck('total_amount', 'TRANS_STATUS')->toArray();

        $reportResult = DB::table('report_transaction_statuses')
            ->where('transaction_type_id', $transactionTypeId)
            ->where('date', $date)
            ->get()->pluck('total_amount', 'transaction_status')->toArray();;


        $this->assertEquals( $liveResult[0], $reportResult[0]);
        $this->assertEquals( $liveResult[1], $reportResult[1]);
        $this->assertEquals( $liveResult[3], $reportResult[3]);
    }

    public function testMBTransactionStatus() {
        $year = 2022;
        $month = 9;
        $transactionStatus = 1;
        list($startTime, $endTime) = get_time_range_from_month($year, $month);


        $reportResult = DB::selectOne("SELECT SUM(total_count) sumTotalCount FROM report_mb_transaction_statuses
WHERE `year`=? AND `month`=?
AND transaction_status=?", [$year, $month, $transactionStatus]);

        $this->assertEquals(980192, $reportResult->sumTotalCount);

        $snapshotDb = dbConnection('snapshot_stg');
        $snapshotResult = $snapshotDb->selectOne("SELECT COUNT(*) totalCount FROM O2O__ORDER_BASE_TRANS_M
WHERE TRANS_DATETIME BETWEEN ? AND ?
AND TRANS_STATUS=?
AND (SRC_ACCOUNT_TYPE=2 OR DES_ACCOUNT_TYPE=2)", [
            $startTime, $endTime, $transactionStatus
        ]);


        $this->assertEquals(980192, $snapshotResult->totalCount);
    }
}
