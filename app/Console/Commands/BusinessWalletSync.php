<?php

namespace App\Console\Commands;

use App\Models\BusinessWallet;
use App\Models\CrossDb\CrossDbRelation;
use App\Models\CrossDb\RelationBelongsTo;
use App\Models\CrossDb\RelationHasMany;
use Illuminate\Console\Command;
use Illuminate\Database\Query\Builder;

class BusinessWalletSync extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'BusinessWalletSync';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    public function handle()
    {



        $this->syncViDonViChapNhanThanhToan();
    }

    private function syncViDonViChapNhanThanhToan() {

    }
    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handleOld()
    {
        $db = dbConnection('EWALLET');
        $walletTypeFee = 4;
        $walletTypeVat = 5;
        $accounts = $db->table('accounting_service_entries')
            ->select('id', 'account_number', 'account_name')
            ->where('account_number', '!=', '{customer_account_number}')
            ->get();
        dd($accounts);
        $exists = [];
        foreach ($lines as $line) {

            list($code, $type) = explode("\t", $line);
            $code = trim($code);
            if (isset( $exists[$code])) {
                continue;
            }
            $exists[$code] = true;

            $wallet = $db->table('wallet_account')
                ->where('code', $code)
                ->first();
            if ($wallet) {
                $entry = new BusinessWallet();

                $count = BusinessWallet::query()
                    ->where('wallet_code', $code)
                    ->count();
                if ($count === 0) {
                    $entry->wallet_id = $wallet->id;
                    $entry->wallet_code = $code;
                    $entry->wallet_name = $wallet->wallet_name;
                    $entry->wallet_type_id = $type;
                    try {
                        $entry->save();
                        echo "INSERTED $code\n";
                    } catch (\Throwable $e) {
                        $this->error($e->getMessage());
                    }
                }


            }
        }


        return 0;
    }
}

