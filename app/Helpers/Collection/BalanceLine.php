<?php
/**
 * BalanceLine.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types = 1);
namespace FireflyIII\Helpers\Collection;

use Carbon\Carbon;
use FireflyIII\Models\Budget as BudgetModel;
use Illuminate\Support\Collection;

/**
 *
 * Class BalanceLine
 *
 * @package FireflyIII\Helpers\Collection
 */
class BalanceLine
{

    const ROLE_DEFAULTROLE = 1;
    const ROLE_TAGROLE     = 2;
    const ROLE_DIFFROLE    = 3;

    /** @var  Collection */
    protected $balanceEntries;

    /** @var BudgetModel */
    protected $budget;
    /** @var  Carbon */
    protected $endDate;
    /** @var int */
    protected $role = self::ROLE_DEFAULTROLE;
    /** @var  Carbon */
    protected $startDate;

    /**
     *
     */
    public function __construct()
    {
        $this->balanceEntries = new Collection;

    }

    /**
     * @param BalanceEntry $balanceEntry
     */
    public function addBalanceEntry(BalanceEntry $balanceEntry)
    {
        $this->balanceEntries->push($balanceEntry);
    }

    /**
     * @return Collection
     */
    public function getBalanceEntries(): Collection
    {
        return $this->balanceEntries;
    }

    /**
     * @param Collection $balanceEntries
     */
    public function setBalanceEntries(Collection $balanceEntries)
    {
        $this->balanceEntries = $balanceEntries;
    }

    /**
     * @return BudgetModel
     */
    public function getBudget(): BudgetModel
    {
        return $this->budget ?? new BudgetModel;
    }

    /**
     * @param BudgetModel $budget
     */
    public function setBudget(BudgetModel $budget)
    {
        $this->budget = $budget;
    }

    /**
     * @return Carbon
     */
    public function getEndDate()
    {
        return $this->endDate;
    }

    /**
     * @param Carbon $endDate
     */
    public function setEndDate($endDate)
    {
        $this->endDate = $endDate;
    }

    /**
     * @return int
     */
    public function getRole(): int
    {
        return $this->role;
    }

    /**
     * @param int $role
     */
    public function setRole(int $role)
    {
        $this->role = $role;
    }

    /**
     * @return Carbon
     */
    public function getStartDate()
    {
        return $this->startDate;
    }

    /**
     * @param Carbon $startDate
     */
    public function setStartDate($startDate)
    {
        $this->startDate = $startDate;
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        if ($this->getBudget() instanceof BudgetModel && !is_null($this->getBudget()->id)) {
            return $this->getBudget()->name;
        }
        if ($this->getRole() == self::ROLE_DEFAULTROLE) {
            return trans('firefly.no_budget');
        }
        if ($this->getRole() == self::ROLE_TAGROLE) {
            return trans('firefly.coveredWithTags');
        }
        if ($this->getRole() == self::ROLE_DIFFROLE) {
            return trans('firefly.leftUnbalanced');
        }

        return '';
    }

    /**
     * If a BalanceLine has a budget/repetition, each BalanceEntry in this BalanceLine
     * should have a "spent" value, which is the amount of money that has been spent
     * on the given budget/repetition. If you subtract all those amounts from the budget/repetition's
     * total amount, this is returned:
     *
     * @return string
     */
    public function leftOfRepetition(): string
    {
        $start = $this->budget->amount ?? '0';
        /** @var BalanceEntry $balanceEntry */
        foreach ($this->getBalanceEntries() as $balanceEntry) {
            $start = bcadd($balanceEntry->getSpent(), $start);
        }

        return $start;
    }
}
