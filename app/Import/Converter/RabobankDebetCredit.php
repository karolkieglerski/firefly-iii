<?php
/**
 * RabobankDebetCredit.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

namespace FireflyIII\Import\Converter;

use Log;

/**
 * Class RabobankDebetCredit
 *
 * @package FireflyIII\Import\Converter
 */
class RabobankDebetCredit implements ConverterInterface
{

    /**
     * @param $value
     *
     * @return int
     */
    public function convert($value): int
    {
        Log::debug('Going to convert ', ['value' => $value]);

        if ($value === 'D') {
            Log::debug('Return -1');

            return -1;
        }

        Log::debug('Return 1');

        return 1;
    }
}
