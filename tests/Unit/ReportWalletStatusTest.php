<?php

namespace Tests\Unit;

use App\Console\Commands\WalletIndexProcess;
use App\Models\CrossDb\CrossDbRelation;
use App\Models\CrossDb\RelationBelongsTo;
use Illuminate\Database\Query\Builder;
use Tests\TestCase;

class ReportWalletStatusTest extends TestCase
{
    private string $reportDate = '2022-09-30';
    private string $missingWalletId = '330fdbee-41ce-4d8e-bc0a-b89215f9847e';

    public function testCheckMissingWallet()
    {
        $recentWallets =  WalletIndexProcess::getRecentActiveWallet($this->reportDate, false);
        $activeWallets =  WalletIndexProcess::getActiveWallet($this->reportDate, false);
        $this->assertCount(3503, $recentWallets);
        $this->assertCount(11119, $activeWallets);
        $activeWalletMap = [];

        foreach ($activeWallets as $walletId) {
            $activeWalletMap[$walletId] = true;
        }

        $missing = [];
        foreach ($recentWallets as $walletId) {
            if (!isset($activeWalletMap[$walletId])) {
                $missing[]  =$walletId;
            }
        }

        $this->assertCount(0, $missing);
    }


    private function getAmountWalletTypeGroup(array $walletIds)
    {
        $wallets = array_map(function($walletId) {
            return (object)[
                'id' => $walletId,
                'amount' => 0,
            ];
        }, $walletIds);

        $amountGroup = [];
        foreach ($wallets as $wallet) {
            $wallet->balance_at_date = getWalletBalanceAtDate($wallet->id, $this->reportDate);
            attachWalletTypeId($wallets,  $this->reportDate);

            if (!isset($amountGroup[$wallet->wallet_type_id])) {
                $amountGroup[$wallet->wallet_type_id] = 0;
            }
            $amountGroup[$wallet->wallet_type_id] += $wallet->balance_at_date;
        }

        return $amountGroup;
    }

    public function testWalletActiveAllOrRecentActive()
    {
        $recentWalletIds = WalletIndexProcess::getRecentActiveWallet($this->reportDate, false);
        $this->assertCount(3503, $recentWalletIds);
        $recentActiveGroup = $this->getAmountWalletTypeGroup($recentWalletIds);
        $recentFormatAmounted = numberFormatArray($recentActiveGroup);


        $this->assertEquals('671,041,234', $recentFormatAmounted[1]);
        $this->assertEquals('72,433,310', $recentFormatAmounted[3]);


        $activeWalletIds = WalletIndexProcess::getActiveWallet($this->reportDate, false);
        $recentActiveGroup = $this->getAmountWalletTypeGroup($activeWalletIds);
        $activeFormatAmounted = numberFormatArray($recentActiveGroup);
        $this->assertEquals('671,041,234', $activeFormatAmounted[1]);
        $this->assertEquals('72,433,310', $activeFormatAmounted[3]);
        $this->assertCount(11120, $activeWalletIds);
    }

    public function testActiveWallet() {
        $walletIds = WalletIndexProcess::getActiveWallet($this->reportDate, false);

        $this->assertCount(11120, $walletIds);

    }
}
