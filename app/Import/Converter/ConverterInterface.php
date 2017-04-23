<?php
/**
 * ConverterInterface.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

namespace FireflyIII\Import\Converter;

use FireflyIII\User;

/**
 * Interface ConverterInterface
 *
 * @package FireflyIII\Import\Converter
 */
interface ConverterInterface
{
    /**
     * @param $value
     *
     */
    public function convert($value);

    /**
     * @return int
     */
    public function getCertainty(): int;

    /**
     * @param array $config
     */
    public function setConfig(array $config);

    /**
     * @param bool $doMap
     */
    public function setDoMap(bool $doMap);

    /**
     * @param array $mapping
     *
     */
    public function setMapping(array $mapping);

    /**
     * @param User $user
     */
    public function setUser(User $user);
}
