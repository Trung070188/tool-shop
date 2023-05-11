<?php
namespace Tests\Unit;
use Tests\TestCase;

class WalletBalanceAtDateTest extends TestCase
{
    /**
     * @throws \Exception
     */
    public function testWalletBalanceAtDate() {
        $this->assertEquals("production", config_env('REPORT_ENV'));

        $walletId = '763252b7-d39b-44c5-8388-af433a92dc37';
        $date = '2022-09-02';
        $time = "$date 23:59:59";

        $cacheBalance = getWalletBalanceAtDate($walletId, $date, true);
        $db = dbConnection(DB_CONNECTION_EWALLET);

        $walletTrans = $db->table('wallet_transaction')
            ->where(function($q) use($walletId) {
                $q->where('receiver_wallet', $walletId)
                    ->orWhere('sender_wallet', $walletId);
            })
            ->where('create_on', '<=', $time)
            ->orderBy('create_on', 'desc')
            ->first();

        $this->assertEquals(2459935, $cacheBalance);
        $this->assertEquals($walletId, $walletTrans->sender_wallet);
        $this->assertEquals($walletTrans->sender_balance, $cacheBalance);
    }
}
