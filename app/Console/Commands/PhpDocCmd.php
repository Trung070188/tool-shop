<?php

namespace App\Console\Commands;

use App\Helpers\PhpDoc;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PhpDocCmd extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'phpdoc {table}';

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
        //
        //PhpDoc::renderTableField('orders');
        $table = $this->argument('table');
        try {
            PhpDoc::renderTableField($table);
        } catch (\Exception $e) {
            echo $e->getMessage() . "\n";
        }
    }
}
