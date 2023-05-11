<?php

namespace App\Console\Commands;

use App\Helpers\PhpDoc;
use App\Models\Inventory;
use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DBTestCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dbtest';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        try {
            $config = config('database.connections.mysql');
            $this->info("CONFIG DUMP");
            print_r($config);

            DB::selectOne('SELECT  1');
            $this->info('DB CONNECTION TEST SUCCEED');

        } catch (\Throwable $e) {
            $this->error($e);
        }
    }
}
