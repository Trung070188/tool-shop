<?php

namespace App\Jobs;

use App\Services\SyncSapService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyncSapUser implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $page;
    protected $pageSize;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($page, $pageSize)
    {
        $this->page = $page;
        $this->pageSize = $pageSize;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $syncSap = new SyncSapService();
        $syncSap->getListUsers($this->page, $this->pageSize);
    }
}
