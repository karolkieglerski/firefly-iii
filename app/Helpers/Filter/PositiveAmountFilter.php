<?php
/**
 * PositiveAmountFilter.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

namespace FireflyIII\Helpers\Filter;

use FireflyIII\Models\Transaction;
use Illuminate\Support\Collection;
use Log;

/**
 * Class PositiveAmountFilter
 *
 * This filter removes entries with a negative amount (the original modifier is -1).
 *
 * This filter removes transactions with either a positive amount ($parameters = 1) or a negative amount
 * ($parameter = -1). This is helpful when a Collection has you with both transactions in a journal.
 *
 * @package FireflyIII\Helpers\Filter
 */
class PositiveAmountFilter implements FilterInterface
{
    /**
     * @param Collection $set
     *
     * @return Collection
     */
    public function filter(Collection $set): Collection
    {
        return $set->filter(
            function (Transaction $transaction) {
                // remove by amount
                if (bccomp($transaction->transaction_amount, '0') === 1) {
                    Log::debug(sprintf('Filtered #%d because amount is %f.', $transaction->id, $transaction->transaction_amount));

                    return null;
                }

                return $transaction;
            }
        );
    }
}