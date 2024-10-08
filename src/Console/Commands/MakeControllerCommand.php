<?php

namespace Bleuren\LaravelApi\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

class MakeControllerCommand extends Command
{
    protected $signature = 'make:api-controller {name}';

    protected $description = 'Create a new API controller with service injection';

    protected $files;

    public function __construct(Filesystem $files)
    {
        parent::__construct();
        $this->files = $files;
    }

    public function handle()
    {
        $name = $this->argument('name');
        $this->createController($name);
        $this->updateRouteFile($name);
    }

    protected function createController($name)
    {
        $controllerTemplate = str_replace(
            ['{{controllerName}}', '{{serviceName}}'],
            [$name, $name],
            $this->getStub('Controller')
        );

        $path = app_path("Http/Controllers/{$name}Controller.php");
        $this->makeDirectory(dirname($path));
        $this->files->put($path, $controllerTemplate);
        $this->info('Controller created successfully.');
    }

    protected function updateRouteFile($name)
    {
        $routePath = base_path('routes/web.php');
        $routeContent = $this->files->get($routePath);

        $newRoute = "\nRoute::apiResource('".Str::plural(strtolower($name))."', {$name}Controller::class);";
        $useStatement = "use App\Http\Controllers\\{$name}Controller;";

        // 檢查是否已經有了 use 語句
        if (! Str::contains($routeContent, $useStatement)) {
            // 在文件開頭添加 use 語句，但在 <?php 標籤之後
            $routeContent = preg_replace(
                '/^<\?php\s+/i',
                "<?php\n\n$useStatement\n",
                $routeContent
            );
        }

        // 檢查是否已經有了路由
        if (! Str::contains($routeContent, $newRoute)) {
            // 在文件末尾添加新路由
            $routeContent .= $newRoute;
        }

        $this->files->put($routePath, $routeContent);
        $this->info('Route added successfully.');
    }

    protected function getStub($type)
    {
        return $this->files->get(__DIR__."/stubs/$type.stub");
    }

    protected function makeDirectory($path)
    {
        if (! $this->files->isDirectory($path)) {
            $this->files->makeDirectory($path, 0777, true, true);
        }
    }
}
