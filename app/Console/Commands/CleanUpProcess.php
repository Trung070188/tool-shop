<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CleanUpProcess extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'CleanUpProcess';

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
        if (config('app.env') === 'production') {
            $prev = date('Y-m-d H:i:s', strtotime('-90 days'));
        } else {
            $prev = date('Y-m-d H:i:s', strtotime('-3 days'));
        }

        $deleted = DB::table('xlogger')->where('time', '<=', $prev)->delete();
        $this->info("DELETED $deleted FROM xlogger");
        return 0;
    }
}
