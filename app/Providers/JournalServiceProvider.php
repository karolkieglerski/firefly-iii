<?php
/**
 * JournalServiceProvider.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types = 1);


namespace FireflyIII\Providers;

use FireflyIII\Exceptions\FireflyException;
use Illuminate\Foundation\Application;
use Illuminate\Support\ServiceProvider;

/**
 * Class JournalServiceProvider
 *
 * @package FireflyIII\Providers
 */
class JournalServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->registerRepository();
        $this->registerTasker();
        $this->registerCollector();
    }

    private function registerCollector() {
        $this->app->bind(
            'FireflyIII\Helpers\Collector\JournalCollectorInterface',
            function (Application $app, array $arguments) {
                if (!isset($arguments[0]) && $app->auth->check()) {
                    return app('FireflyIII\Helpers\Collector\JournalCollector', [auth()->user()]);
                }
                if (!isset($arguments[0]) && !$app->auth->check()) {
                    throw new FireflyException('There is no user present.');
                }

                return app('FireflyIII\Helpers\Collector\JournalCollector', $arguments);
            }
        );
    }

    private function registerRepository()
    {
        $this->app->bind(
            'FireflyIII\Repositories\Journal\JournalRepositoryInterface',
            function (Application $app, array $arguments) {
                if (!isset($arguments[0]) && $app->auth->check()) {
                    return app('FireflyIII\Repositories\Journal\JournalRepository', [auth()->user()]);
                }
                if (!isset($arguments[0]) && !$app->auth->check()) {
                    throw new FireflyException('There is no user present.');
                }

                return app('FireflyIII\Repositories\Journal\JournalRepository', $arguments);
            }
        );
    }

    private function registerTasker()
    {
        $this->app->bind(
            'FireflyIII\Repositories\Journal\JournalTaskerInterface',
            function (Application $app, array $arguments) {
                if (!isset($arguments[0]) && $app->auth->check()) {
                    return app('FireflyIII\Repositories\Journal\JournalTasker', [auth()->user()]);
                }
                if (!isset($arguments[0]) && !$app->auth->check()) {
                    throw new FireflyException('There is no user present.');
                }

                return app('FireflyIII\Repositories\Journal\JournalTasker', $arguments);
            }
        );
    }
}
