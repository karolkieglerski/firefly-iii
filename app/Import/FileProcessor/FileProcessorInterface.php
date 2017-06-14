<?php
/**
 * FileProcessorInterface.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

namespace FireflyIII\Import\FileProcessor;

use FireflyIII\Models\ImportJob;
use Illuminate\Support\Collection;

/**
 * Interface FileProcessorInterface
 *
 * @package FireflyIII\Import\FileProcessor
 */
interface FileProcessorInterface
{


    /**
     * @return Collection
     */
    public function getObjects(): Collection;

    /**
     * @return bool
     */
    public function run(): bool;

    /**
     * @param ImportJob $job
     *
     * @return FileProcessorInterface
     */
    public function setJob(ImportJob $job): FileProcessorInterface;
}