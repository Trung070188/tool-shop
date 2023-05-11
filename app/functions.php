<?php

use App\Models\BusinessWallet;
use App\Models\CrossDb\CrossDbRelation;
use App\Models\CrossDb\RelationHasMany;
use App\Models\WalletType;
use \App\Models\ReportProcessState;
use Illuminate\Database\Query\Builder;
use \Illuminate\Support\Facades\DB;
use \Illuminate\Support\Facades\Log;

const GOD_WALLET_ID = 'c702d471-eb97-42c2-8153-6f88d67976ad';
const WALLET_TYPE_TKNN_ID  =15;
const CUSTOMER_ACCOUNT_TYPE_MB = 2;
const CUSTOMER_ACCOUNT_TYPE_POSTPAY = 1;
CONST DB_CONNECTION_EWALLET = 'EWALLET';
CONST DB_CONNECTION_O2O = 'O2O';

function asset_js($paths)
{
    if (!is_iterable($paths)) {
        $paths = [$paths];
    }

    $content = '';
    foreach ($paths as $_path) {
        $content .= sprintf('<script src="%s"></script>',
            asset($_path) . '?v=' . filemtime(public_path($_path))
        );
    }

    return $content;
}

function asset_css($paths)
{
    if (!is_iterable($paths)) {
        $paths = [$paths];
    }
    $content = '';
    foreach ($paths as $_path) {
        $content .= sprintf('<link href="%s" rel="stylesheet">',
            asset($_path) . '?v=' . filemtime(public_path($_path))
        );
    }

    return $content;
}

function errors2message($errors)
{
    $message = [];
    foreach ($errors as $err => $m) {
        $message[] = $m;
    }
    return implode('<br>', $message);
}

function d1($var)
{
    echo "<pre>";
    echo json_encode($var, 128);
    echo "</pre>";
    die;
}

function word_normalized($word)
{
    $word = preg_replace('/[\-_]/', ' ', $word);
    return ucwords($word);
}

function date_parse_range($date_str)
{
    if (!is_string($date_str)) {
        return false;
    }

    $tmp = explode('_', $date_str);
    if (count($tmp) >= 2) {
        list($start, $end) = $tmp;
        $start = date('Y-m-d', strtotime($start));
        $end = date('Y-m-d', strtotime($end));
        return compact('start', 'end');
    }

    return false;
}

function columns2rules($col)
{
    /**
     * 0 => {#726
     * +"Field": "id"
     * +"Type": "int(10) unsigned"
     * +"Null": "NO"
     * +"Key": "PRI"
     * +"Default": null
     * +"Extra": "auto_increment"
     * }
     */
    $rules = [];
    if ($col->Null === 'NO') {
        $rules[] = 'required';
    }

    if (preg_match('/^(int|varchar|tinyint)\((\d+)/', $col->Type, $m)) {

        if (isset($m[1]) && $m[1] === 'int' || $m[1] === 'tinyint' || $m[1] === 'bigint') {
            $rules[] = 'numeric';
        }
        if ($m[1] === 'varchar' && isset($m[2])) {
            $rules[] = 'max:' . $m[2];
        }
    } else if ($col->Type === 'date') {
        $rules[] = 'date_format:Y-m-d';
    } else if ($col->Type === 'timestamp' || $col->Type === 'datetime') {
        $rules[] = 'date_format:Y-m-d H:i:s';
    }

    return implode('|', $rules);
}

function mail_send($to, $subject, $content)
{
    $SSOHelper = new \App\Helpers\SSOHelper();
    $params = [
        'mail' => $to,
        "subject" => $subject,
        'content' => $content,
        'type' => 'raw',
    ];

    return $SSOHelper->requestPost('/api/sendmail', [
        'form_params' => $params
    ], true);
}

function timeAgo($timestamp = 0, $now = 0): string
{

    // Set up an array of time intervals.
    $intervals = array(
        60 * 60 * 24 * 365 => 'Năm',
        60 * 60 * 24 * 30 => 'Tháng',
        60 * 60 * 24 * 7 => 'Tuần',
        60 * 60 * 24 => 'Ngày',
        60 * 60 => 'Giờ',
        60 => 'Phút',
        1 => 'Giây',
    );

    // Get the current time if a reference point has not been provided.
    if (0 === $now) {
        $now = time();
    }

    // Make sure the timestamp to check predates the current time reference point.
    if ($timestamp > $now) {
        throw new \Exception('Timestamp postdates the current time reference point');
    }

    // Calculate the time difference between the current time reference point and the timestamp we're comparing.
    $time_difference = (int)abs($now - $timestamp);

    if ($time_difference > 2592000) {
        return date('d/m/Y H:i', $timestamp);
    }

    if ($time_difference < 60) {
        return 'Vừa xong';
    }
    // Check the time difference against each item in our $intervals array. When we find an applicable interval,
    // calculate the amount of intervals represented by the the time difference and return it in a human-friendly
    // format.
    foreach ($intervals as $interval => $label) {

        // If the current interval is larger than our time difference, move on to the next smaller interval.
        if ($time_difference < $interval) {
            continue;
        }

        // Our time difference is smaller than the interval. Find the number of times our time difference will fit into
        // the interval.
        $time_difference_in_units = round($time_difference / $interval);

        if ($time_difference_in_units <= 1) {
            $time_ago = sprintf('1 %s trước',
                $label
            );
        } else {
            $time_ago = sprintf('%s %s trước',
                $time_difference_in_units,
                $label
            );
        }

        return $time_ago;
    }

    return 'Unknown';
}

function front_url($path)
{
    return config('app.front_url') . '/' . ltrim($path, '/');
}

function curl_get_json($url, $query = null, $basicAuth = null)
{
    $res = curl_get($url, $query, $basicAuth);
    return json_decode($res, true);
}

function curl_get($url, $query = null, $basicAuth = null)
{
    if (is_array($query)) {
        $tmp = explode('?', $url);
        $url = $tmp[0];
        if (isset($tmp[1])) {
            parse_str($tmp[1], $cQuery);
            $query = array_merge($cQuery, $query);
        }
        $url .= '?' . http_build_query($query);
    }

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_ENCODING, "gzip");
    curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/52.0.2743.116 Safari/537.36");

    if ($basicAuth) {
        curl_setopt($ch, CURLOPT_USERPWD, $basicAuth);
    }
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
// receive server response ...
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    $server_output = curl_exec($ch);

    if ($server_output === false) {
        trigger_error(curl_error($ch));
    }

    curl_close($ch);
    /// file_put_contents($filename, $server_output);
    return $server_output;
}

function curl_post($url, $data = array(), $jsonContentType = false)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, 1);

    if ($jsonContentType) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
    } else {
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    }
    //  curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    // curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

// in real life you should use something like:
// curl_setopt($ch, CURLOPT_POSTFIELDS,
//          http_build_query(array('postvar1' => 'value1')));

// receive server response ...
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $server_output = curl_exec($ch);

    curl_close($ch);
    return $server_output;
}

function generate_id($url)
{
    return trim(str_replace('/', '_', $url), '_');
}

function image_url($path)
{
    return config('app.image_url') . '/' . ltrim($path, '/');
}

/**
 * @return \App\Models\User
 */
function auth_user()
{
    return auth()->user();
}

function googleClientId(): string
{
    static $clientID;
    if ($clientID) {
        return $clientID;
    }

    return config('services.google.client_id');
}

function vue(array $vars = [], array $jsonData = [])
{
    if (isset($var['title'])) {
        $jsonData['pageTitle'] = $var['title'];
    }

    $vars['jsonData'] = $jsonData;

    return view('admin.layouts.main', $vars);
}

function get_gravatar(string $id, $s = 128, $d = 'identicon', $r = 'g', $img = false, $atts = array()): string
{
    $url = 'https://www.gravatar.com/avatar/';
    $url .= md5(strtolower(trim($id)));
    $url .= "?s=$s&d=$d&r=$r";
    if ($img) {
        $url = '<img src="' . $url . '"';
        foreach ($atts as $key => $val)
            $url .= ' ' . $key . '="' . $val . '"';
        $url .= ' />';
    }

    return $url;
}

function reportError(Throwable $e)
{
    try {
        $request = [
            '$_POST' => $_POST,
            '$_GET' => $_GET,
            'BODY' => file_get_contents('php://input'),
            '$_SERVER' => $_SERVER,
        ];

        \Illuminate\Support\Facades\DB::table('error_reports')
            ->insert([
                'app' => 'fulfillment-admin',
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'time' => date('Y-m-h H:i:s'),
                'queries' => json_encode(\Illuminate\Support\Facades\DB::getQueryLog(), JSON_PRETTY_PRINT),
                'request' => json_encode($request, JSON_PRETTY_PRINT),
            ]);
    } catch (\Throwable $e1) {
        \Illuminate\Support\Facades\Log::error($e1);
    }
}

function exception_truncate(string $trace): array
{
    $traces = explode("\n", $trace);
    $result = [];
    $basePath = base_path();
    foreach ($traces as $trace) {
        if (!str_contains($trace, DIRECTORY_SEPARATOR . 'vendor')) {
            $result[] = trim(str_replace($basePath, '', $trace));
        }
    }

    return $result;
}

function getPermissionNameMap($permissions): array
{
    $map = [];
    foreach ($permissions as $permission) {
        $key = $permission->module . '.' . $permission->name;
        $map[$key] = true;
        $children = $permission->children;
        if (count($children) > 0) {
            $childMap = getPermissionNameMap($children);
            foreach ($childMap as $k => $v) {
                $map[$k] = true;
            }
        }
    }

    return $map;
}


/**
 * @param string $name
 * @return \Illuminate\Database\ConnectionInterface
 */
function dbConnection(string $name): \Illuminate\Database\ConnectionInterface
{
    return DB::connection($name);

}

function config_env(string $key, $defaultValue = null)
{
    static $env;
    if ($env === false) {
        return env($key, $defaultValue);
    }

    if ($env === null) {
        $envFile = __DIR__ . '/../config/env/env.php';
        if (!is_file($envFile)) {
            $env = false;
            return env($key, $defaultValue);
        }

        $env = require $envFile;
    }

    return $env[$key] ?? $defaultValue;
}

function array_flip_value(array $array, mixed $value): array
{
    $res = [];
    foreach ($array as $item) {
        $res[$item] = $value;
    }

    return $res;
}

function excelColLetterToIndex($char): int {
    static $cache;
    if (!$cache) {
        $cache = [];
        for ($i = 0; $i < 1000; $i++) {
            $cache[excelColumnFromIndex($i)] = $i;
        }
    }

    return $cache[strtoupper($char)];
}

function excelColumnFromIndex($num)
{
    $numeric = $num % 26;
    $letter = chr(65 + $numeric);
    $num2 = intval($num / 26);

    if ($num2 > 0) {
        return excelColumnFromIndex($num2 - 1) . $letter;
    }

    return $letter;
}

function excel2ArrayCallback(string $inputFileName, callable $callback, $sheetIndex = 0)
{
    $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();

    $spreadsheet = $reader->load($inputFileName);
    $result = [];
    $sheet = $spreadsheet->getSheet($sheetIndex);
    $highestRow = $sheet->getHighestRow();
    $highestColumn = $sheet->getHighestColumn();

    //  Loop through each row of the worksheet in turn
    for ($row = 1; $row <= $highestRow; ++$row) {
        //  Read a row of data into an array
        $rowData = $sheet->rangeToArray(
            'A' . $row . ':' . $highestColumn . $row,
            null,
            true,
            false
        );

        $returnRow = $rowData[0];
        //  Insert row data array into your database of choice here

        $callback($returnRow);
    }
}

function excel2Array(string $inputFileName, $sheetIndex = 0)
{
    $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();

    /** Load $inputFileName to a Spreadsheet Object  */

//        $inputFileType = IOFactory::identify($inputFileName);
    //      $spreadsheet = $reader->load($inputFileType);
    /**  Create a new Reader of the type defined in $inputFileType  */
    ///$reader = IOFactory::createReader('xlsx');
    /**  Load $inputFileName to a Spreadsheet Object  */
    $spreadsheet = $reader->load($inputFileName);
    $result = [];
    $sheet = $spreadsheet->getSheet($sheetIndex);
    $highestRow = $sheet->getHighestRow();
    $highestColumn = $sheet->getHighestColumn();

    //  Loop through each row of the worksheet in turn
    for ($row = 1; $row <= $highestRow; ++$row) {
        //  Read a row of data into an array
        $rowData = $sheet->rangeToArray(
            'A' . $row . ':' . $highestColumn . $row,
            null,
            true,
            false
        );

        $returnRow = $rowData[0];
        //  Insert row data array into your database of choice here

        $result[] = $returnRow;
    }

    return $result;
}

function isArrayAllNull(array $input): bool
{
    foreach ($input as $value) {
        $value = trim((string)$value);
        if ($value !== '') {
            return false;
        }
    }

    return true;
}

function parseFromDateAuto(string $class, string $fromDate): string
{
    if (empty($fromDate)) {
        $output = '2021-11-18';
    } else if ($fromDate === 'auto') {
        /**
         * @var ReportProcessState $report
         */
        $report = ReportProcessState::query()
            ->where('class', $class)
            ->where('status', 1)
            ->orderBy('date', 'desc')
            ->first();
        if (!$report) {
            $output = '2021-11-18';
        } else {
            $output = $report->date;
        }
    } else {
        $output = $fromDate;
    }

    return $output;
}

function forEachMonth(string $class, callable $callback, $fromDate): void
{
    $fromDate = parseFromDateAuto($class, $fromDate);

    $env = config_env('REPORT_ENV');
    $start = strtotime($fromDate);
    $end = time();
    $time = $start;
    while ($time < $end) {
        $year = date('Y', $time);
        $month = date('m', $time);

        $state = new ReportProcessState();
        $state->date = "$year-$month-01";
        $state->class = $class;
        $state->env = $env;

        try {
            $callback($year, $month);
            $state->result = 'OK';
            $state->status = 1;
        } catch (\Throwable $ex) {
            Log::error($ex);
            echo "ERROR: " . $ex->getMessage()."\n";
            $state->status = 2;
            $state->result = $ex->getMessage() ."\n" . $ex->getTraceAsString();
        }

        $state->save();

        $time = strtotime('+1 month', $time);
    }
}

function forEachDate(string $class, callable $callback, $fromDate = '2021-11-18'): void
{
    $fromDate = parseFromDateAuto($class, $fromDate);
    $env = config_env('REPORT_ENV');
    $start = strtotime($fromDate);
    $end = time();

    for ($time = $start; $time <= $end; $time += 86400) {
        $date = date('Y-m-d', $time);
        $timeStart = "$date 00:00:00";
        $timeEnd = "$date 23:59:59";
        $state = new ReportProcessState();
        $state->date = $date;
        $state->class = $class;
        $state->env = $env;

        try {
            $callback($date, $timeStart, $timeEnd);
            $state->result = 'OK';
            $state->status = 1;
        } catch (\Throwable $e) {
            Log::error($e);
            $state->status = 2;
            echo "ERROR: " . $e->getMessage()."\n";
            $state->result = $e->getMessage() ."\n" . $e->getTraceAsString();
        }

        $state->save();

    }
}

function getWalletBalanceAtDate($walletId, $date, $throw = false) {
    static $cache;
    if ($cache === null) {
        $cache = [];
    }

    if (isset($cache[$date])) {
        return $cache[$date][$walletId] ?? 0;
    }

    $env = config_env('REPORT_ENV');
    $cacheDir = "cache.$env";
    $filename = storage_path("$cacheDir/wallet-balances") . '/' . $date . '.json';

    if (!is_file($filename)) {
        if ($throw) {
            throw new \Exception("$filename not found");
        } else {
            echo "Warning: $filename not found\n";
        }
        return 0;
    }

    $cache[$date] = json_decode(file_get_contents($filename), true);


    return $cache[$date][$walletId] ?? 0;
}

function getReportDatabases() {
    static $databases;
    if ($databases) {
        return $databases;
    }

    $env = config_env('REPORT_ENV', 'local');

    $databases = DB::table('db_connections')
        ->selectRaw('id, name')
        ->where('env', $env)
        ->where('type', 'report')
        ->get();



    return $databases;
}

function buildDonViChapNhanThanToanMap($date = null): array
{
    $time = null;
    if ($date) {
        $time = strtotime($date);
    }

    $db = dbConnection('O2O');
    $merchantContractSigns =  $db->table('MERCHANT_CONTRACT_SIGN')
        ->selectRaw('CUSTOMER_ID,CONTRACT_CUS_SIGN_DATETIME')
        ->where('CONTRACT_SIGN_STATUS', 1)
        ->get();

    CrossDbRelation::attachHasMany($merchantContractSigns, new RelationHasMany(
        table: 'CUSTOMER_ACCOUNT',
        alias: 'CustomerAccounts',
        fields: ['ACCOUNT_ID', 'CUSTOMER_ID', 'WALLET_ID', 'ACCOUNT_TYPE'],
        foreignKey: 'CUSTOMER_ID',
        localKey: 'CUSTOMER_ID',
        connection: 'O2O',
        query: function(Builder $builder) {
            $builder->whereIn('ACCOUNT_TYPE', [1,3]);
        }
    ));

    $walletTypeMap = [];

    foreach ($merchantContractSigns as $contractSign) {
        foreach ($contractSign->CustomerAccounts as $customerAccount) {
            $CONTRACT_CUS_SIGN_TIME = strtotime($contractSign->CONTRACT_CUS_SIGN_DATETIME);
            if ($time === null || $CONTRACT_CUS_SIGN_TIME <= $time) {
                $walletTypeMap[$customerAccount->WALLET_ID] = [
                    'CUSTOMER_ID' => $contractSign->CUSTOMER_ID,
                    'CONTRACT_CUS_SIGN_DATETIME' => $contractSign->CONTRACT_CUS_SIGN_DATETIME,
                    'CONTRACT_CUS_SIGN_TIME' => strtotime($contractSign->CONTRACT_CUS_SIGN_DATETIME),
                ];
            }
        }
    }

    return $walletTypeMap;
}

function attachWalletTypeId($wallets, $date = null): void
{
    static $businessWalletMap;
    static $dvcnttCache = [];

    $cacheKey = $date === null ? 'all' : $date;

    if (empty($dvcnttCache[$cacheKey])) {
        $dvcnttCache = [];
        $dvcnttCache[$cacheKey] = buildDonViChapNhanThanToanMap($date);
    }

    $dvcnttMap = $dvcnttCache[$cacheKey];

    if (!$businessWalletMap) {
        $businessWallets = BusinessWallet::query()->with('walletType')->get();
        $businessWalletMap = [];
        foreach ($businessWallets as $businessWallet) {
            $walletType = $businessWallet->walletType;
            $businessWalletMap[$businessWallet->wallet_id] = [
                'wallet_type_id' => $businessWallet->wallet_type_id,
                'wallet_type_name' => $walletType ? $walletType->name : ''
            ];
        }
    }

    foreach ($wallets as $wallet) {
        $walletId = $wallet->id;
        $wallet->wallet_type_id = 1;
        $wallet->wallet_type_name =  'Ví cá nhân';
        if (isset($businessWalletMap[$wallet->id])) {
            $wallet->wallet_type_id = $businessWalletMap[$walletId]['wallet_type_id'];
            $wallet->wallet_type_name = $businessWalletMap[$walletId]['wallet_type_name'];
        } else if (isset($dvcnttMap[$walletId])) {
            $wallet->wallet_type_id = 3;
            $wallet->wallet_type_name = 'Ví Đơn vị Chấp nhận thanh toán';
        }
    }
}

function get_percent($numerator, $denominator) {

    return $denominator == 0 ? 0 : number_format(100*($numerator/$denominator), 2);
}

function get_time_range_from_month($year, $month): array
{
    $time = strtotime("$year-$month");
    $startTime = date('Y-m-d', $time) . ' 00:00:00';
    $endTime = date("Y-m-t", $time). ' 23:59:59';
    return [ $startTime, $endTime];
}

function get_time_range_from_date($year, $month, $date): array
{
    $time = strtotime("$year-$month-$date");
    $date = date('Y-m-d', $time);
    $startTime = $date . ' 00:00:00';
    $endTime = $date. ' 23:59:59';
    return [ $startTime, $endTime, $date];
}

function get_order_base_trans_id_ranges($year, $month, $date) {
    list($startTime, $endTime) = get_time_range_from_date($year, $month, $date);

    $lastTrans = dbConnection('O2O')
        ->table('ORDER_BASE_TRANS_M')
        ->selectRaw('M_BASE_TRANS_ID,TRANS_DATETIME')
        ->where('TRANS_DATETIME', '<=', $endTime)
        ->orderBy('M_BASE_TRANS_ID', 'DESC')
        ->limit(2)
        ->get();

    $firstTrans = dbConnection('O2O')
        ->table('ORDER_BASE_TRANS_M')
        ->selectRaw('M_BASE_TRANS_ID,TRANS_DATETIME')
        ->where('TRANS_DATETIME', '>=', $endTime)
        ->orderBy('M_BASE_TRANS_ID', 'ASC')
        ->limit(5)
        ->get();

    dd($firstTrans);

}

function dbTableTruncate(string $name): void
{
    $count = DB::table($name)->count();
    DB::table($name)->truncate();
    echo "TRUNCATE TABLE $name. DELETD $count\n";
}


function getValueInRange($value, $minValue, $maxValue)
{
    if ($value < $minValue) {
        return $minValue;
    }

    if ($value > $maxValue) {
        return $maxValue;
    }

    return $value;
}

function numberFormatArray($array): array
{
    foreach ($array as &$item) {
        $item = number_format($item);
    }

    return $array;
}
