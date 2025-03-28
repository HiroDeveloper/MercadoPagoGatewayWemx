<?php

namespace Modules\MercadoPago\Providers;

use Illuminate\Support\ServiceProvider;

class GatewayPackServiceProvider extends ServiceProvider
{

    protected string $moduleName = 'MercadoPago';

    protected string $moduleNameLower = 'mercadopago';

    public function boot(): void
    {
        $this->registerConfig();
        $this->loadTranslations();
        $this->registerViews();

    }

    private function registerViews(): void
    {
        $viewPath = resource_path('views/modules/' . $this->moduleNameLower);
        $sourcePath = module_path($this->moduleName, 'Resources/views');

        if (is_dir($sourcePath)) {
            $this->publishes([
                $sourcePath => $viewPath
            ], ['views', $this->moduleNameLower . '-module-views']);

            $this->loadViewsFrom(array_merge($this->getPublishableViewPaths(), [$sourcePath]), $this->moduleNameLower);
        }
    }

    private function getPublishableViewPaths(): array
    {
        $paths = [];
        foreach (\Config::get('view.paths') as $path) {
            $moduleViewPath = $path . '/modules/' . $this->moduleNameLower;
            if (is_dir($moduleViewPath)) {
                $paths[] = $moduleViewPath;
            }
        }
        return $paths;
    }

    protected function registerConfig(): void
    {
        $this->publishes([
            module_path($this->moduleName, 'Config/config.php') => config_path($this->moduleNameLower . '.php'),
        ], 'config');
        $this->mergeConfigFrom(
            module_path($this->moduleName, 'Config/config.php'), $this->moduleNameLower
        );
    }
}
