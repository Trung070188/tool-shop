<?php

/**
 * DB logger for dev
 * Class XLogger
 * @author QuanTran
 */

class XLogger {
    private $requestData = [];
    private $redis;
    private $nativeRedis = false;


    public function __construct()
    {
        date_default_timezone_set('Asia/Ho_Chi_Minh');
        $this->requestData = $this->getRequestData();
    }

    private function getRedisKey(): string
    {
        return 'omipos.xlogger';
    }

    public function _redis()
    {
        if ($this->redis) {
            return $this->redis;
        }
        try {
            $redisDb = (int)env('REDIS_DB', 4);

            if (class_exists('\Redis')) {
                $this->redis = new \Redis();
                $this->redis->connect(env('REDIS_HOST'));
                $this->redis->select($redisDb);
                $this->nativeRedis = true;
            } else {
                $this->redis = new Predis\Client([
                    'scheme' => 'tcp',
                    'host' => '127.0.0.1',
                    'port' => 6379,
                ]);
                $this->redis->select($redisDb);
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error($e);
        }

        return $this->redis;
    }

    private function getClientIp()
    {
        static $ip;

        if (isset ($ip)) {
            return $ip;
        }

        if (!empty ($_SERVER['HTTP_CLIENT_IP']) ) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } else if (!empty ($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else if (!empty ($_SERVER['HTTP_X_FORWARDED'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED'];
        } else if ( !empty ($_SERVER['HTTP_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_FORWARDED_FOR'];
        } else if ( !empty ($_SERVER['HTTP_FORWARDED']) ) {
            $ip = $_SERVER['HTTP_FORWARDED'];
        } else if( !empty ($_SERVER['REMOTE_ADDR'])) {
            $ip = $_SERVER['REMOTE_ADDR'];
        } else {
            $ip = 'UNKNOWN';
        }


        return $ip;
    }

    private function getRequestData() {
        $entry = [];
        $entry['response'] = '';
        $entry['request_uri'] = $_SERVER['REQUEST_URI'];
        $entry['http_method'] = $_SERVER['REQUEST_METHOD'];


        $entry['request_headers'] = json_encode(getallheaders(), JSON_PRETTY_PRINT);
        $entry['event_type'] = 'Front';
        $entry['time'] = date('Y-m-d H:i:s');
        $entry['ip'] = $this->getClientIp();
        if ( $entry['http_method']  === "GET") {
            $params = $_GET;
        } else if ($entry['http_method'] === 'POST') {
            $params = $_POST;
            if (empty($_POST)) {
                $params = file_get_contents("php://input");
                $decoded = json_decode($params, true);
                if ($decoded) {
                    $params = $decoded;
                }
            }
        } else {
            $params = file_get_contents("php://input");
        }


        $entry['parameters'] = is_array($params) ? json_encode($params, JSON_PRETTY_PRINT) : $params;

        $entry['user_agent'] = @$_SERVER['HTTP_USER_AGENT'];
        return $entry;
    }

    private function createTable() {
        \DB::select('CREATE TABLE `xlogger` (
              `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
              `username` varchar(200) DEFAULT NULL,
              `request_uri` varchar(255) DEFAULT NULL,
              `http_method` varchar(20) DEFAULT NULL,
              `http_code` int(4) DEFAULT NULL,
              `parameters` text,
              `response` text,
              `request_headers` text,
              `response_headers` text,
              `user_agent` varchar(255) DEFAULT NULL,
              `ip` varchar(50) DEFAULT NULL,
              `time` datetime DEFAULT NULL,
              `event_type` varchar(20) DEFAULT NULL,
              `message` varchar(255) DEFAULT NULL,
              `exception` varchar(100) DEFAULT NULL,
              `stack_trace` text,
              `query` text,
              `execution_time` double DEFAULT NULL,
              PRIMARY KEY (`id`),
              KEY `request_uri` (`request_uri`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8
            ');
    }

    public function setException(\Throwable $e) {
        $this->requestData['message'] = $e->getMessage();
        $this->requestData['exception'] = get_class($e);
        $this->requestData['stack_trace'] = $e->getTraceAsString();
    }

    private function parseHeaders($string)
    {
        $headers = [];
        $lines = explode("\n", $string);
        $lines = array_map('trim', $lines);

        foreach ($lines as $line) {
            if (empty($line)) {
                continue;
            }
            @list($key, $value) = explode(':', $line);
            $headers[trim($key)] = trim($value);
        }

        return json_encode($headers, JSON_PRETTY_PRINT);
    }

    public function push($response) {
        try {
            $uri = $this->requestData['request_uri'];
            $isApi = substr($uri, 0, 4) === '/api';
            $exts = explode('.', $uri);
            $ext = strtolower(array_pop($exts));

            $ignored = [
                'png' => true,
                'jpeg' => true,
                'jpg' => true,
                'gif' => true,
                'mp4' => true,
                'webp' => true,
                'js' => true,
                'css' => true,
                'ico' => true,
                'map' => true,
                'svg' => true
            ];

            if (isset($ignored[$ext])) {
                return;
            }

            $user = auth()->user();

            $username = null;
            if ($user) {
                if (!empty($user->email)) {
                    $username = 'user:'.  $user->email;
                } else {
                    $username = 'user:' . $user->id;
                }
            }

            $this->requestData['username'] = $username;
            $this->requestData['http_code'] = $response->getStatusCode();
            $resData = $response->getContent();

            $this->requestData['response'] = $resData;
            $this->requestData['response_headers'] = (string)$response->headers;
            $queryLogs = \DB::getQueryLog();
            $this->requestData['query'] =
                json_encode(['count' => count($queryLogs), 'queries' => $queryLogs], JSON_PRETTY_PRINT);
            $env = config('app.env');
            $f = @$this->requestData['response'][0];
            if ($f === '{' || $f === '[') {
                $isApi = true;
            }

            if ($isApi) {
                $this->requestData['response'] = json_encode(json_decode($this->requestData['response']), JSON_PRETTY_PRINT);
            } else {
                $this->requestData['response'] = '<html/> Len='. mb_strlen($this->requestData['response']);
            }

            if ($env !== 'production') {

                try {
                    \DB::table('xlogger')->insert( $this->requestData);
                } catch (\Exception $e) {
                    $message = $e->getMessage();
                    if (strpos($message,'Base table or view not found')) {
                        try {
                            $this->createTable();
                        } catch (\Exception $e) {

                        }
                    }
                    \Illuminate\Support\Facades\Log::error($e);
                }

            } else {
                $this->_redis()->lpush($this->getRedisKey(), serialize( $this->requestData));
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error($e);
        }
    }


}

$GLOBALS ['xlogger'] = new XLogger();
