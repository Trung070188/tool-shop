<?php

namespace App\Http\Controllers\Api;

use App\Services\QueryBuilderService;
use Illuminate\Http\Request;

class QueryBuilderController
{
    public function handle(Request $request) {
        if (config('app.env') !== 'local') {
            return [
                'code'  => 1,
                'message' => 'Only on LOCAL'
            ];
        }

        $service = new QueryBuilderService();
        return $service->handle($request);
    }
}
