<?php

namespace App\Console\Commands;

use App\Models\Permission;
use App\Services\ModuleManager;
use Illuminate\Console\Command;
use \ReflectionMethod;

class PermissionCmd extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'permission {action}';

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
        $action = $this->argument('action');
        $this->$action();
        return 0;
    }

    private function dump()
    {
        $root = Permission::find(1);
        if (!$root) {
            $root = new Permission();
            $root->id = 1;
            $root->name = 'root';
            $root->class = '*';
            $root->action = '*';
            $root->module = 'ROOT';
            $root->display_name = 'ROOT PERMISSION';
            $root->save();
        }



        $this->info("Starting permission dump");

        $modules = [
            [
                'baseClass' => 'App\Http\Controllers\Admin\AdminBaseController',
                'name' => ModuleManager::MODULE_PLS_MAIN,
                'dir' => app_path('Http/Controllers/Admin'),
                'namespace' => 'Admin',
            ]
        ];

        $moduleList = [];
        foreach ($modules as $module) {
            $moduleName = $module['name'];
            $moduleRoot = Permission::query()->where('name', $moduleName . '_Root')
                ->where('parent_id', 1)->first();

            if (!$moduleRoot) {
                $moduleRoot = new Permission();
                $moduleRoot->name = $moduleName . '_Root';
                $moduleRoot->module = $moduleName;
                $moduleRoot->parent_id = 1;
                $moduleRoot->class = $moduleName . '.*';
                $moduleRoot->action = $moduleName  .'.*';
                $moduleRoot->display_name = "$moduleName ROOT";
                $moduleRoot->save();
            }

            $moduleList[] = compact('moduleRoot', 'module');
        }

        foreach ($moduleList as $m) {
            $this->scan($m['moduleRoot'], $m['module']);
        }
    }

    private function scan(Permission $moduleRoot, $module) {

        $baseClass = $module['baseClass'];
        $namespace = $module['namespace'];
        $moduleName = $module['name'];
        $dir = $module['dir'];

        $files = scandir($dir);
        $inserted = 0;

        foreach ($files as $idx => $file) {
            if ($idx < 2) {
                continue;
            }

            $filename = $dir.'/'.$file;

            if (is_file($filename)) {
                $info = pathinfo($file);

                if (isset($info['filename'])) {
                    $controllerName = $info['filename'];

                    if (isset($ignoredClass[$controllerName])) {
                        continue;
                    }


                    $class = new \ReflectionClass('\App\Http\Controllers\\' . $namespace . '\\'.$controllerName);
                    $parent = $class->getParentClass();
                    if (!$parent || $parent->name !== $baseClass) {
                        continue;
                    }

                    $namespaces = explode("\\", $class->name);
                    $baseClassName = last($namespaces);
                    $baseClassName = str_replace('Controller', '', $baseClassName);

                    $parent = Permission::query()
                        ->where('module', $moduleName)
                        ->where('class', $class->name)->first();
                    if (!$parent) {
                        $parent = new Permission();
                    }

                    $parent->name = $baseClassName;
                    $parent->class = $class->name;
                    $parent->action = '*';
                    $parent->module = $moduleName;
                    $parent->parent_id = $moduleRoot->id;
                    $parent->display_name = $baseClassName;
                    $parent->save();

                    $methods = $class->getMethods(ReflectionMethod::IS_PUBLIC);

                    foreach ($methods as $method) {
                        if (!str_starts_with($method->name, '__')) {
                            $permission = Permission::where('class', $method->class)
                                ->where('module', $moduleName)
                                ->where('action', $method->name)
                                ->first();

                            if (!$permission) {
                                $permission = new Permission();

                                $permission->class = $method->class;
                                $permission->module = $moduleName;
                                $permission->action = $method->name;
                                $permission->name = $baseClassName. '.' . $method->name;
                                $permission->parent_id = $parent->id;
                                $permission->save();
                                $inserted++;
                            }

                        }

                    }


                }
            }
        }

        $this->info("INSERTED $inserted");
    }
}
