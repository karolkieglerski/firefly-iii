<?php
/**
 * BillRepositoryInterface.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace FireflyIII\Repositories\Bill;

use Carbon\Carbon;
use FireflyIII\Models\Bill;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\User;
use Illuminate\Support\Collection;

/**
 * Interface BillRepositoryInterface
 *
 * @package FireflyIII\Repositories\Bill
 */
interface BillRepositoryInterface
{
    /**
     * @param Bill $bill
     *
     * @return bool
     */
    public function destroy(Bill $bill): bool;

    /**
     * Find a bill by ID.
     *
     * @param int $billId
     *
     * @return Bill
     */
    public function find(int $billId): Bill;

    /**
     * Find a bill by name.
     *
     * @param string $name
     *
     * @return Bill
     */
    public function findByName(string $name): Bill;

    /**
     * @return Collection
     */
    public function getActiveBills(): Collection;

    /**
     * @return Collection
     */
    public function getBills(): Collection;

    /**
     * Gets the bills which have some kind of relevance to the accounts mentioned.
     *
     * @param Collection $accounts
     *
     * @return Collection
     */
    public function getBillsForAccounts(Collection $accounts): Collection;

    /**
     * Get the total amount of money paid for the users active bills in the date range given.
     *
     * @param Carbon $start
     * @param Carbon $end
     *
     * @return string
     */
    public function getBillsPaidInRange(Carbon $start, Carbon $end): string;

    /**
     * Get the total amount of money due for the users active bills in the date range given.
     *
     * @param Carbon $start
     * @param Carbon $end
     *
     * @return string
     */
    public function getBillsUnpaidInRange(Carbon $start, Carbon $end): string;

    /**
     * @param Bill $bill
     *
     * @return string
     */
    public function getOverallAverage(Bill $bill): string;

    /**
     * @param Bill   $bill
     * @param Carbon $start
     * @param Carbon $end
     *
     * @return Collection
     */
    public function getPaidDatesInRange(Bill $bill, Carbon $start, Carbon $end): Collection;

    /**
     * Between start and end, tells you on which date(s) the bill is expected to hit.
     *
     * @param Bill   $bill
     * @param Carbon $start
     * @param Carbon $end
     *
     * @return Collection
     */
    public function getPayDatesInRange(Bill $bill, Carbon $start, Carbon $end): Collection;

    /**
     * @param Bill $bill
     *
     * @return Collection
     */
    public function getPossiblyRelatedJournals(Bill $bill): Collection;

    /**
     * @param Bill   $bill
     * @param Carbon $date
     *
     * @return string
     */
    public function getYearAverage(Bill $bill, Carbon $date): string;

    /**
     * Given a bill and a date, this method will tell you at which moment this bill expects its next
     * transaction. Whether or not it is there already, is not relevant.
     *
     * @param Bill   $bill
     * @param Carbon $date
     *
     * @return \Carbon\Carbon
     */
    public function nextDateMatch(Bill $bill, Carbon $date): Carbon;

    /**
     * @param Bill   $bill
     * @param Carbon $date
     *
     * @return \Carbon\Carbon
     */
    public function nextExpectedMatch(Bill $bill, Carbon $date): Carbon;

    /**
     * @param Bill               $bill
     * @param TransactionJournal $journal
     *
     * @return bool
     */
    public function scan(Bill $bill, TransactionJournal $journal): bool;

    /**
     * @param User $user
     */
    public function setUser(User $user);

    /**
     * @param array $data
     *
     * @return Bill
     */
    public function store(array $data): Bill;

    /**
     * @param Bill  $bill
     * @param array $data
     *
     * @return Bill
     */
    public function update(Bill $bill, array $data): Bill;

}
