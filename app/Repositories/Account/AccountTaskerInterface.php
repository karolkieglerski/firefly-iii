<?php
/**
 * AccountTaskerInterface.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace FireflyIII\Repositories\Account;

use Carbon\Carbon;
use FireflyIII\User;
use Illuminate\Support\Collection;

/**
 * Interface AccountTaskerInterface
 *
 * @package FireflyIII\Repositories\Account
 */
interface AccountTaskerInterface
{
    /**
     * @param Collection $accounts
     * @param Collection $excluded
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @see AccountTasker::amountInPeriod()
     *
     * @return string
     */
    public function amountInInPeriod(Collection $accounts, Collection $excluded, Carbon $start, Carbon $end): string;

    /**
     * @param Collection $accounts
     * @param Collection $excluded
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @see AccountTasker::amountInPeriod()
     *
     * @return string
     */
    public function amountOutInPeriod(Collection $accounts, Collection $excluded, Carbon $start, Carbon $end): string;

    /**
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return array
     */
    public function getAccountReport(Collection $accounts, Carbon $start, Carbon $end): array;

    /**
     * @param User $user
     */
    public function setUser(User $user);

}
