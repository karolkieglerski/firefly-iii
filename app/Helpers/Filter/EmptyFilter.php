<?php
/**
 * EmptyFilter.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

namespace FireflyIII\Helpers\Filter;

use Illuminate\Support\Collection;

/**
 * Class EmptyFilter
 *
 * @package FireflyIII\Helpers\Filter
 */
class EmptyFilter implements FilterInterface
{


    /**
     * @param Collection $set
     *
     * @return Collection
     */
    public function filter(Collection $set): Collection
    {
        return $set;
    }
}