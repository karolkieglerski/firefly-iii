<?php
/**
 * TestDataSeeder.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types = 1);

use FireflyIII\Support\Migration\TestData;
use Illuminate\Database\Seeder;

/**
 * Class TestDataSeeder
 */
class TestDataSeeder extends Seeder
{
    /**
     * TestDataSeeder constructor.
     */
    public function __construct()
    {
    }

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $disk = Storage::disk('seeds');
        $env  = App::environment();
        Log::debug('Environment is ' . $env);
        $fileName = 'seed.' . $env . '.json';
        if ($disk->exists($fileName)) {
            Log::debug('Now seeding ' . $fileName);
            $file = json_decode($disk->get($fileName), true);

            if (is_array($file)) {
                // run the file:
                TestData::run($file);

                return;
            }
            Log::error(sprintf('No valid data found (%s) for environment %s', $fileName, $env));

            return;

        }
        Log::error(sprintf('No seed file (%s) for environment %s', $fileName, $env));
    }
}
