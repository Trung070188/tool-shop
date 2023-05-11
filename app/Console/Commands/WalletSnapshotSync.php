<?php

namespace App\Console\Commands;

use App\Models\BusinessWallet;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class WalletSnapshotSync extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'WalletSnapshotSync';

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
        $db = dbConnection('EWALLET');
        $inserted = 0;
        $wallets= BusinessWallet::query()
            ->with('walletType')->get();

        $businessWalletMap = [];
        foreach ($wallets as $wallet) {
            $businessWalletMap[$wallet->wallet_id] = $wallet;
        }

        $this->info("TRUNCATE TABLE `snapshot_wallet_account`");
        DB::table('snapshot_wallet_account')->truncate();
        $db->table('wallet_account')->orderBy('create_on', 'asc')
            ->chunk(1000, function($walletAccounts) use(&$inserted, $businessWalletMap) {
                $bulkData = [];
                foreach ($walletAccounts as $walletAccount) {
                    $walletTypeId = 1;

                    if (isset($businessWalletMap[$walletAccount->id])) {
                        $businessWallet = $businessWalletMap[$walletAccount->id];
                        $walletTypeId = $businessWallet->wallet_type_id;
                    }

                    $bulkData[] = [
                        'id' => $walletAccount->id,
                        'code' => $walletAccount->code,
                        'wallet_name'  => $walletAccount->wallet_name,
                        'amount' => $walletAccount->amount,
                        'blocked_amount' => $walletAccount->blocked_amount,
                        'create_on' => $walletAccount->create_on,
                        'update_on' => $walletAccount->update_on,
                        'wallet_type_id' => $walletTypeId
                    ];
                }

                DB::table('snapshot_wallet_account')->insert($bulkData);
                $inserted += count($bulkData);
                echo "Inserted $inserted\n";
            });

        return 0;
    }
}
