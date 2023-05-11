<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class Handler extends ExceptionHandler
{
    public static $exceptions = [];
    /**
     * A list of the exception types that are not reported.
     *
     * @var array
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     *
     * @return void
     */
    public function register()
    {
        $this->reportable(function (Throwable $e) {
            static::$exceptions[] = $e;

            try {
                DB::connection('exception')->table('error_logs')
                    ->insert([
                        'exception' => get_class($e),
                        'message' => $e->getMessage(),
                        'trace' => json_encode(exception_truncate($e->getTraceAsString()), JSON_PRETTY_PRINT),
                        'time' => date("Y-m-d H:i:s"),
                        'sent' => 0
                    ]);
            } catch (\Throwable $e) {
                Log::error($e);
            }
        });
    }
}
