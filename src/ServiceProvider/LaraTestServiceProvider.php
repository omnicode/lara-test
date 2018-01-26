<?php
namespace LaraTest\ServiceProvider;

use Illuminate\Support\ServiceProvider;
use LaraLink\Components\LinkRoute;
use LaraLink\Links\ItemActionLink;
use LaraTest\Console\Commands\MakeTestController;
use LaraTest\Console\Commands\MakeTestModel;

class LaraTestServiceProvider extends ServiceProvider
{
    public function boot()
    {

        $configPath = __DIR__ . DIRECTORY_SEPARATOR . '..'. DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR
            .'config' . DIRECTORY_SEPARATOR . 'test.php';
        $this->mergeConfigFrom($configPath, 'lara_test');

        if ($this->app->runningInConsole()) {
            $this->commands([
                MakeTestController::class,
                MakeTestModel::class,
            ]);
        }
    }
}
