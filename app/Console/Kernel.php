<?php
/**
 * Kernel.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace FireflyIII\Console;

use FireflyIII\Console\Commands\CreateImport;
use FireflyIII\Console\Commands\EncryptFile;
use FireflyIII\Console\Commands\Import;
use FireflyIII\Console\Commands\ScanAttachments;
use FireflyIII\Console\Commands\UpgradeDatabase;
use FireflyIII\Console\Commands\UpgradeFireflyInstructions;
use FireflyIII\Console\Commands\UseEncryption;
use FireflyIII\Console\Commands\VerifyDatabase;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

/**
 * Class Kernel
 *
 * @package FireflyIII\Console
 */
class Kernel extends ConsoleKernel
{
    /**
     * The bootstrap classes for the application.
     *
     * Next upgrade verify these are the same.
     *
     * @var array
     */
    protected $bootstrappers
        = [
            'Illuminate\Foundation\Bootstrap\LoadEnvironmentVariables',
            'Illuminate\Foundation\Bootstrap\LoadConfiguration',
            //'FireflyIII\Bootstrap\ConfigureLogging',
            'Illuminate\Foundation\Bootstrap\HandleExceptions',
            'Illuminate\Foundation\Bootstrap\RegisterFacades',
            'Illuminate\Foundation\Bootstrap\SetRequestForConsole',
            'Illuminate\Foundation\Bootstrap\RegisterProviders',
            'Illuminate\Foundation\Bootstrap\BootProviders',
        ];

    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands
        = [
            UpgradeFireflyInstructions::class,
            VerifyDatabase::class,
            Import::class,
            CreateImport::class,
            EncryptFile::class,
            ScanAttachments::class,
            UpgradeDatabase::class,
            UseEncryption::class,
        ];

    /**
     * Register the Closure based commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        require base_path('routes/console.php');
    }
}
