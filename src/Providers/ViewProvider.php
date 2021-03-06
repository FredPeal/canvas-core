<?php

declare(strict_types=1);

namespace Canvas\Providers;

use Phalcon\Di\ServiceProviderInterface;
use Phalcon\DiInterface;
use Phalcon\Mvc\View\Engine\Volt as VoltEngine;
use function Canvas\Core\appPath;
use Phalcon\Mvc\View\Simple as SimpleView;
use Phalcon\Cache\Frontend\None as NoneCache;
use Phalcon\Cache\Frontend\Output as FrontenCacheOutput;
use Phalcon\Cache\Backend\File as BackendFileCache;

class ViewProvider implements ServiceProviderInterface
{
    /**
     * @param DiInterface $container
     */
    public function register(DiInterface $container)
    {
        $config = $container->get('config');

        /**
         * Setting up the view component.
         */
        $container->set('view', function () use ($config, $container) {
            $view = new SimpleView();
            $view->setViewsDir($config->filesystem->local->path . '/view/');
            $view->registerEngines([
                '.volt' => function ($view, $container) use ($config) {
                    $volt = new VoltEngine($view, $container);
                    $volt->setOptions([
                        //CACHE save DISABLED IN DEV ENVIRONMENT
                        'compiledPath' => appPath('storage/cache/volt/'),
                        'compiledSeparator' => '_',
                        'compileAlways' => !$config->app->production,
                    ]);

                    return $volt;
                },
            ]);

            return $view;
        });

        /**
         * View cache.
         */
        $container->set(
            'viewCache',
            function () use ($config) {
                if (!$config->app->production) {
                    $frontCache = new NoneCache();
                } else {
                    //Cache data for one day by default
                    $frontCache = new FrontenCacheOutput([
                        'lifetime' => 172800,
                    ]);
                }
                return new BackendFileCache($frontCache, [
                    'cacheDir' => appPath('storage/cache/volt/'),
                    'prefix' => $config->app->id . '-',
                ]);
            }
        );
    }
}
