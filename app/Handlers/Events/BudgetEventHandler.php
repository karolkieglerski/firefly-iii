<?php
/**
 * BudgetEventHandler.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace FireflyIII\Handlers\Events;


use FireflyIII\Events\StoredBudgetLimit;
use FireflyIII\Events\UpdatedBudgetLimit;
use FireflyIII\Models\LimitRepetition;
use Illuminate\Database\QueryException;
use Log;

/**
 * Handles budget related events.
 *
 * Class BudgetEventHandler
 *
 * @package FireflyIII\Handlers\Events
 */
class BudgetEventHandler
{
    /**
     * This method creates a new budget limit repetition when a new budget limit has been created.
     *
     * @param StoredBudgetLimit $event
     *
     * @return bool
     */
    public function storeRepetition(StoredBudgetLimit $event):bool
    {
        $budgetLimit = $event->budgetLimit;
        $end         = $event->end;
        $set         = $budgetLimit->limitrepetitions()
                                   ->where('startdate', $budgetLimit->startdate->format('Y-m-d 00:00:00'))
                                   ->where('enddate', $end->format('Y-m-d 00:00:00'))
                                   ->get();
        if ($set->count() == 0) {
            $repetition            = new LimitRepetition;
            $repetition->startdate = $budgetLimit->startdate;
            $repetition->enddate   = $end;
            $repetition->amount    = $budgetLimit->amount;
            $repetition->budgetLimit()->associate($budgetLimit);

            try {
                $repetition->save();
            } catch (QueryException $e) {
                Log::error('Trying to save new LimitRepetition failed: ' . $e->getMessage());
            }
        }

        if ($set->count() == 1) {
            $repetition         = $set->first();
            $repetition->amount = $budgetLimit->amount;
            $repetition->save();

        }

        return true;
    }


    /**
     * Updates, if present the budget limit repetition part of a budget limit.
     *
     * @param UpdatedBudgetLimit $event
     *
     * @return bool
     */
    public function updateRepetition(UpdatedBudgetLimit $event): bool
    {
        $budgetLimit = $event->budgetLimit;
        $end         = $event->end;
        $set         = $budgetLimit->limitrepetitions()
                                   ->where('startdate', $budgetLimit->startdate->format('Y-m-d 00:00:00'))
                                   ->where('enddate', $end->format('Y-m-d 00:00:00'))
                                   ->get();
        if ($set->count() == 0) {
            $repetition            = new LimitRepetition;
            $repetition->startdate = $budgetLimit->startdate;
            $repetition->enddate   = $end;
            $repetition->amount    = $budgetLimit->amount;
            $repetition->budgetLimit()->associate($budgetLimit);

            try {
                $repetition->save();
            } catch (QueryException $e) {
                Log::error('Trying to save new LimitRepetition failed: ' . $e->getMessage());
            }
        }

        if ($set->count() == 1) {
            $repetition         = $set->first();
            $repetition->amount = $budgetLimit->amount;
            $repetition->save();

        }

        return true;
    }
}
