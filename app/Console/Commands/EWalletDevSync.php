<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class EWalletDevSync extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'EWalletDevSync';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    private function syncFromText() {
        $s = 'Ngân hàng Agribank	51114702-30df-437f-9c42-5a7a8b66b49f	888888000008401
5De	5bb27e43-c574-4fcc-be26-18fdbabec8da	888888000011401
Ngân hàng Nam Á	c136848e-616e-49fc-b0be-ed87442275f2	888888000010401
PostSale	163d5151-b2c1-4775-bd23-6357dfb4b498	888888000023401
Imedia	75497b4b-8803-47cc-b58e-9854387b9448	888888000006501
Storage	aa882bf4-846a-4584-bd00-81bbf5071975	888888000009501';
        $lines = explode("\n", $s);
        foreach ($lines as $line) {
            list($fullName, $walletId, $accountNo) = explode("\t", $line);
            #dd($fullName, $walletId, $accountNo);
            echo "Sync $fullName...";
            $cif = substr($accountNo, 0, 12);
            $rawRes = curl_post('http://127.0.0.1:5001/api/wallet/open-customer-wallet', [
                'wallet_id' => $walletId,
                'account_number' => $accountNo,
                'cif' => $cif,
                'full_name' => $fullName,
                'customer_type' => '1'
            ], true);
            $res = json_decode($rawRes, true);
            $this->info($res['error_message']);
        }
    }

    private function fixtkNN() {
        $db = dbConnection('EWALLET');
        $accounts = $db->table('accounting_service_entries')
            ->select('id', 'account_number', 'account_name')
            ->where('account_number', '!=', '{customer_account_number}')
            ->get();


        foreach ($accounts as $account) {
            echo "Sync $account->id...";
            $walletAccount = $db->table('wallet_account')->find($account->id);
            if ($walletAccount) {
                $updated = $db->table('wallet_account')->where('id', $account->id)
                    ->update(['wallet_key' => 'OPS']);

                $this->info($updated);
            } else {
                $this->warn("NOT FOUND");
            }


        }
    }

    private function syncTKNN() {
        $db = dbConnection('EWALLET');
        $_accounts = $db->table('accounting_service_entries')
            ->select('id', 'account_number', 'account_name')
            ->where('account_number', '!=', '{customer_account_number}')
            ->get();
        $accounts = [];
        $exists = [];
        foreach ($_accounts as $acc) {
            if (!isset($exists[$acc->id])) {
                $exists[$acc->id] = true;
                $accounts[] = $acc;
            }
        }

        foreach ($accounts as $account) {
            $cif = substr($account->account_number, 0, 12);
            echo "Sync $account->account_name...";
            #$deleted = $db->table('wallet_account')->where('id', $account->id)->delete();
            #$this->info("DELETED $deleted");

            $rawRes = curl_post('http://127.0.0.1:5001/api/wallet/open-ops-wallet', [
                'wallet_id' => $account->id,
                'account_number' => $account->account_number,
                'cif' => $cif,
                'full_name' => $account->account_name,
                'ops_type' => '1',
                'init_balance' => 0
            ], true);

            $res = json_decode($rawRes, true);
            $this->info($res['error_message']);
        }
    }
    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        if (config_env('REPORT_ENV') !== 'dev') {
            $this->error("REPORT_ENV != dev");
            return;
        }
        $this->syncTKNN();
        die;
        $o2o = dbConnection('O2O');
        $ewallet = dbConnection('EWALLET');
        $total = $o2o->table('CUSTOMER_ACCOUNT')
        ->whereIn('ACCOUNT_TYPE', [1,3])
        ->count();
        $processed = 0;
        $o2o->table('CUSTOMER')->orderBy('CUSTOMER_ID', 'ASC')
            ->chunk(1000, function($customers) use($o2o, $ewallet, $total, &$processed) {

                foreach ($customers as $customer) {

                    $customerAccounts = $o2o->table('CUSTOMER_ACCOUNT')
                        ->where('CUSTOMER_ID', $customer->CUSTOMER_ID)
                        ->whereIn('ACCOUNT_TYPE', [1,3])
                        ->get();
                    if ($customerAccounts->count() > 0) {
                        /*if ($customer->CUSTOMER_ID == 196) {

                        } else {
                            continue;
                        }*/

                        foreach ($customerAccounts as $customerAccount) {
                            try {
                                $walletAccount = $ewallet->table('wallet_account')
                                    ->where('id', $customerAccount->WALLET_ID)
                                    ->first();
                                $processed++;

                                if (!$walletAccount) {
                                    $p = get_percent($processed, $total);
                                    echo "[$p %] Create wallet " . $customerAccount->WALLET_ID . '...';
                                    $rawRes = curl_post('http://127.0.0.1:5001/api/wallet/open-customer-wallet', [
                                        'wallet_id' => $customerAccount->WALLET_ID,
                                        'account_number' => $customerAccount->ACCOUNT_NUMBER,
                                        'cif' => $customerAccount->CIF_NUMBER,
                                        'full_name' => $customer->CUSTOMER_NAME,
                                        'customer_type' => '1'
                                    ], true);
                                    $res = json_decode($rawRes, true);
                                    $this->info($res['error_message']);
                                }
                            } catch (\Throwable $ex) {
                                $this->error($ex);
                            }

                        }
                    }
                }
            });



        return 0;
    }
}
