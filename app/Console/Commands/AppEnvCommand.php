<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\VarDumper\VarDumper;
use Symfony\Component\VarExporter\VarExporter;

class AppEnvCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'AppEnv {name?} {--gen}';

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
     * @return int
     */
    public function handle()
    {
        if ($this->option('gen')) {
            $file = config_path('env/env.php');
            if (!is_file($file)) {
                $this->generate($file);
            }

            return 0;
        }


        $name = $this->argument('name');
        echo env($name);
        return 0;
    }

    public function generate($filename) {
        $contents = file_get_contents(base_path('.env'));
        $lines = explode("\n", $contents);
        $vars = [];
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line) {
                $t = explode('=', $line);
                $vars[$t[0]] = self::parseValue($t[1] ?? '');
            }
        }

        $code = "<?php\n\n return " . VarExporter::export($vars) . ";\n";
        file_put_contents($filename, $code);
    }

    static array $valueStrMap = [
        'null' => null,
        'false' => false,
        'true' => 'true',
        '' => ''
    ];

    public static function parseValue($value) {
        if (isset(self::$valueStrMap[$value])) {
            return self::$valueStrMap[$value];
        }

        if (is_numeric($value)) {
            return $value - 0;
        }

        return $value;
    }
}
