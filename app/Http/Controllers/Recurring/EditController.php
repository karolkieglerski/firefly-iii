<?php
/**
 * EditController.php
 * Copyright (c) 2018 thegrumpydictator@gmail.com
 *
 * This file is part of Firefly III.
 *
 * Firefly III is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Firefly III is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Firefly III. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace FireflyIII\Http\Controllers\Recurring;


use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Models\Recurrence;
use FireflyIII\Models\RecurrenceRepetition;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use FireflyIII\Repositories\Recurring\RecurringRepositoryInterface;
use FireflyIII\Transformers\RecurrenceTransformer;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 *
 * Class EditController
 */
class EditController extends Controller
{
    /** @var BudgetRepositoryInterface */
    private $budgets;
    /** @var RecurringRepositoryInterface */
    private $recurring;

    /**
     *
     */
    public function __construct()
    {
        parent::__construct();

        // translations:
        $this->middleware(
            function ($request, $next) {
                app('view')->share('mainTitleIcon', 'fa-paint-brush');
                app('view')->share('title', trans('firefly.recurrences'));
                app('view')->share('subTitle', trans('firefly.recurrences'));

                $this->recurring = app(RecurringRepositoryInterface::class);
                $this->budgets   = app(BudgetRepositoryInterface::class);

                return $next($request);
            }
        );
    }

    /**
     * @param Request    $request
     * @param Recurrence $recurrence
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     * @throws \FireflyIII\Exceptions\FireflyException
     */
    public function edit(Request $request, Recurrence $recurrence)
    {
        // use transformer:
        $transformer = new RecurrenceTransformer(new ParameterBag);
        $array       = $transformer->transform($recurrence);
        $budgets     = app('expandedform')->makeSelectListWithEmpty($this->budgets->getActiveBudgets());

        // get recurrence type:
        // todo move to repository
        // todo handle old repetition type as well.


        /** @var RecurrenceRepetition $repetition */
        $repetition            = $recurrence->recurrenceRepetitions()->first();
        $currentRepetitionType = $repetition->repetition_type;
        if ('' !== $repetition->repetition_moment) {
            $currentRepetitionType .= ',' . $repetition->repetition_moment;
        }
        // assume repeats forever:
        $repetitionEnd = 'forever';
        // types of repetitions:
        $repetitionEnds = [
            'forever'    => trans('firefly.repeat_forever'),
            'until_date' => trans('firefly.repeat_until_date'),
            'times'      => trans('firefly.repeat_times'),
        ];
        if (null !== $recurrence->repeat_until) {
            $repetitionEnd = 'until_date';
        }
        if ($recurrence->repetitions > 0) {
            $repetitionEnd = 'times';
        }

        // flash some data:
        $preFilled = [
            'transaction_type' => strtolower($recurrence->transactionType->type),
        ];
        $request->flash('preFilled', $preFilled);

        return view('recurring.edit', compact('recurrence', 'array','budgets', 'preFilled', 'currentRepetitionType', 'repetitionEnd', 'repetitionEnds'));
    }


}