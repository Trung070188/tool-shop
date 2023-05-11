<?php
/**
 * Code generator
 * @author quantm@ominext.com
 */

namespace App\Console\Commands;

use App\Helpers\PhpDoc;
use Doctrine\Common\Inflector\Inflector;
use Doctrine\Inflector\InflectorFactory;
use Doctrine\Inflector\Language;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Symfony\Component\VarExporter\VarExporter;

class Generator extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'gen {table} {--force} {--cleanup}';

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
        $table = $this->argument('table');
        try {
            $this->generate($table);
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
    }

    /**
     * @param $table
     * @throws \Symfony\Component\VarExporter\Exception\ExceptionInterface
     * @throws \Throwable
     */
    public function generate($table)
    {
        $force = $this->option('force');
        $inflector = InflectorFactory::createForLanguage(Language::ENGLISH)->build();
        $except = ['id', 'created_at', 'updated_at', 'password', 'deleted_at', 'remember_token'];
        $exceptMap = array_flip($except);

        $_columns = DB::select("SHOW COLUMNS FROM `$table`");
        $columns = [];
        foreach ($_columns as $col) {
            if (!isset($exceptMap[$col->Field])) {
                $columns[] = $col;
            }
        }
        $rules = [];
        foreach ($columns as $col) {
            $rule = columns2rules($col);
            if ($rule) {
                $rules[$col->Field] = $rule;
            }
        }

        $rules = VarExporter::export($rules);

        $fields = collect($columns)->pluck('Field');

        $fillableFields = $fields->flip()
            ->except($except)
            ->keys()
            ->toArray();

        $fillable = VarExporter::export($fillableFields);

        $phpdoc = PhpDoc::renderTableField($table, true);

        $name = ucwords($inflector->singularize(str_replace(' ', '', word_normalized($table))));

        $modelPath = app_path('Models/' . $name . '.php');

        $className = $inflector->pluralize($name) . 'Controller';

        $controllerPath = app_path('Http/Controllers/Admin/' . $className . ".php");


        $now = date('d/m/Y H:i:s');

        $comment = '';
        $routePrefix = '/xadmin';
        $ucTable = ucfirst($inflector->singularize($table));

        $vars = compact('table', 'name', 'phpdoc', 'fillable', 'fields', 'className', 'columns',
            'rules', 'now', 'comment', 'routePrefix', 'ucTable');

        $codeModel = view('templates.Model', $vars)->render();
        $codeController = view('templates.Controller', $vars)->render();
        //$codeViewIndex = view('templates.index', $vars)->render();
        //$codeViewForm = view('templates.form', $vars)->render();
        $codeJsIndex = view('templates.IndexVue', $vars)->render();
        $codeJsForm = view('templates.FormVue', $vars)->render();

        $viewPath = resource_path("views/admin/$table");
        $jsPath = resource_path("js/admin/$table");

        if (!is_dir($viewPath)) {
            mkdir($viewPath, 0755, true);
        }

        if (!is_dir($jsPath)) {
            mkdir($jsPath, 0755, true);
        }

        //$viewPathIndex = $viewPath . '/index.blade.php';
        //$viewPathForm = $viewPath . '/form.blade.php';
        $jsIndexPath = $jsPath . "/{$ucTable}Index.vue";
        $jsFormPath = $jsPath . "/{$ucTable}Form.vue";

        if ($this->option('force') && $this->option('cleanup')) {
            $this->warn("CLEAN $controllerPath");
            unlink($controllerPath);
            //$this->warn("CLEAN $viewPathIndex");
            //unlink($viewPathIndex);
            // $this->warn("CLEAN $viewPathForm");
            // unlink($viewPathForm);
            $this->warn("CLEAN $jsIndexPath");
            unlink($jsIndexPath);
            $this->warn("CLEAN $jsFormPath");
            unlink($jsFormPath);
            $this->warn("CLEAN $modelPath");
            unlink($modelPath);
            $this->warn("CLEAN ALL");
            return;
        }

        $this->saveCode($modelPath, $codeModel);
        $this->saveCode($controllerPath, $codeController);
        //$this->saveCode($viewPathIndex, $codeViewIndex);
        $this->saveCode($jsIndexPath, $codeJsIndex);
        //$this->saveCode($viewPathForm, $codeViewForm);
        $this->saveCode($jsFormPath, $codeJsForm);
        $this->warn("NOTE:");
        //$this->updateJsRegistry($table);
        $this->updateRouteRegistry($table, $className);

        $strImport = "//...\nimport {$ucTable}Index from \"./admin/$table/{$ucTable}Index\";
import {$ucTable}Form from \"./admin/$table/{$ucTable}Form\";\n//...";

        $str = "Added below to resources/js/registry.js \n$strImport\n export default {\n//...\n{$ucTable}Index,\n{$ucTable}Form\n}";
        echo $str . "\n// And remember to restart WEBPACK\n";
        // $routeNote = sprintf("Added\n Route::any('/$table/{action}', '%s')->name('%s'); ", $className, $table);
        //   echo $routeNote . "\nP/s: Remember to restart webpack";
    }

    private function updateJsRegistry($table)
    {
        /*$registryPath = resource_path('/js/routes.js');
        $code = trim(file_get_contents($registryPath));
        $code = str_replace('export default', '', $code);
        $modules = json_decode($code, true);

        $registryMap = array_flip($registry);
        $registryMap["$table/index.js"] = true;
        $registryMap["$table/form.js"] = true;
        $registry = array_keys($registryMap);
        file_put_contents($registryPath, str_replace('\/', '/', json_encode($registry, 128)));
        $this->warn("Saved " . $registryPath)*/;
    }

    private function updateRouteRegistry($table, $className)
    {
        $path = base_path('routes/registry.php');
        $registry = require $path;
        $registryMap = [];
        foreach ($registry as $route) {
            $registryMap[$route['path']] = $route;
        }

        $registryMap["/$table/{action}"] = ['path' => "/$table/{action}", 'action' => $className, 'name' => $table];
        $newRegistry = "<?php\n return\n " . VarExporter::export(array_values($registryMap)) . ";";

        file_put_contents($path, $newRegistry);
        $this->warn("Saved " . $path);
    }

    private function saveCode($path, $code)
    {
        $force = $this->option('force');
        $path = str_replace('\\', '/', $path);
        if (!$force && is_file($path)) {
            $this->error("$path already exists. Ignored. Added --force option to overwrite");
            return;
        }

        file_put_contents($path, $code);
        $this->info("Saved " . $path);
    }
}
