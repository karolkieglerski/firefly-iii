<?php
/**
 * BudgetController.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace FireflyIII\Http\Controllers\Chart;

use Carbon\Carbon;
use FireflyIII\Generator\Chart\Budget\BudgetChartGeneratorInterface;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Models\Budget;
use FireflyIII\Models\LimitRepetition;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use FireflyIII\Support\CacheProperties;
use Illuminate\Support\Collection;
use Log;
use Navigation;
use Preferences;
use Response;

/**
 * Class BudgetController
 *
 * @package FireflyIII\Http\Controllers\Chart
 */
class BudgetController extends Controller
{

    /** @var BudgetChartGeneratorInterface */
    protected $generator;

    /**
     * BudgetController constructor.
     */
    public function __construct()
    {
        parent::__construct();
        // create chart generator:
        $this->generator = app(BudgetChartGeneratorInterface::class);
    }

    /**
     * checked
     *
     * @param BudgetRepositoryInterface $repository
     * @param Budget                    $budget
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function budget(BudgetRepositoryInterface $repository, Budget $budget)
    {
        $first = $repository->firstUseDate($budget);
        $range = Preferences::get('viewRange', '1M')->data;
        $last  = session('end', new Carbon);

        $cache = new CacheProperties();
        $cache->addProperty($first);
        $cache->addProperty($last);
        $cache->addProperty('budget');

        if ($cache->has()) {
            return Response::json($cache->get());
        }

        $final = clone $last;
        $final->addYears(2);

        $budgetCollection = new Collection([$budget]);
        $last             = Navigation::endOfX($last, $range, $final); // not to overshoot.
        $entries          = new Collection;
        while ($first < $last) {

            // periodspecific dates:
            $currentStart = Navigation::startOfPeriod($first, $range);
            $currentEnd   = Navigation::endOfPeriod($first, $range);
            // sub another day because reasons.
            $currentEnd->subDay();
            $spent = $repository->spentInPeriod($budgetCollection, new Collection, $currentStart, $currentEnd);
            $entry = [$first, ($spent * -1)];
            $entries->push($entry);
            $first = Navigation::addPeriod($first, $range, 0);
        }

        $data = $this->generator->budgetLimit($entries, 'month');
        $cache->store($data);

        return Response::json($data);
    }

    /**
     * Shows the amount left in a specific budget limit.
     *
     * @param BudgetRepositoryInterface $repository
     * @param Budget                    $budget
     * @param LimitRepetition           $repetition
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function budgetLimit(BudgetRepositoryInterface $repository, Budget $budget, LimitRepetition $repetition)
    {
        $start = clone $repetition->startdate;
        $end   = $repetition->enddate;
        $cache = new CacheProperties();
        $cache->addProperty($start);
        $cache->addProperty($end);
        $cache->addProperty('budget-limit');
        $cache->addProperty($budget->id);
        $cache->addProperty($repetition->id);

        if ($cache->has()) {
            return Response::json($cache->get());
        }

        $entries          = new Collection;
        $amount           = $repetition->amount;
        $budgetCollection = new Collection([$budget]);
        while ($start <= $end) {
            $spent  = $repository->spentInPeriod($budgetCollection, new Collection, $start, $start);
            $amount = bcadd($amount, $spent);
            $entries->push([clone $start, round($amount, 2)]);

            $start->addDay();
        }
        $data = $this->generator->budgetLimit($entries, 'month_and_day');
        $cache->store($data);

        return Response::json($data);
    }

    /**
     * Shows a budget list with spent/left/overspent.
     *
     * @param BudgetRepositoryInterface $repository
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function frontpage(BudgetRepositoryInterface $repository)
    {
        Log::debug('Hello');
        $start = session('start', Carbon::now()->startOfMonth());
        $end   = session('end', Carbon::now()->endOfMonth());
        // chart properties for cache:
        $cache = new CacheProperties();
        $cache->addProperty($start);
        $cache->addProperty($end);
        $cache->addProperty('budget');
        $cache->addProperty('all');
        if ($cache->has()) {
            return Response::json($cache->get());
        }
        $budgets     = $repository->getActiveBudgets();
        $repetitions = $repository->getAllBudgetLimitRepetitions($start, $end);
        $allEntries  = new Collection;

        /** @var Budget $budget */
        foreach ($budgets as $budget) {
            // get relevant repetitions:
            $reps = $this->filterRepetitions($repetitions, $budget, $start, $end);

            if ($reps->count() === 0) {
                $collection = $this->spentInPeriodSingle($repository, $budget, $start, $end);
                $allEntries = $allEntries->merge($collection);
                continue;
            }
            $collection = $this->spentInPeriodMulti($repository, $budget, $reps);
            $allEntries = $allEntries->merge($collection);

        }
        $entry = $this->spentInPeriodWithout($repository, $start, $end);
        $allEntries->push($entry);
        $data = $this->generator->frontpage($allEntries);
        $cache->store($data);

        return Response::json($data);
    }

    /**
     * @param BudgetRepositoryInterface $repository
     * @param Budget                    $budget
     * @param Carbon                    $start
     * @param Carbon                    $end
     * @param Collection                $accounts
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function period(BudgetRepositoryInterface $repository, Budget $budget, Carbon $start, Carbon $end, Collection $accounts)
    {
        // chart properties for cache:
        $cache = new CacheProperties();
        $cache->addProperty($start);
        $cache->addProperty($end);
        $cache->addProperty($accounts);
        $cache->addProperty($budget->id);
        $cache->addProperty('budget');
        $cache->addProperty('period');
        if ($cache->has()) {
            return Response::json($cache->get());
        }
        // loop over period, add by users range:
        $current     = clone $start;
        $viewRange   = Preferences::get('viewRange', '1M')->data;
        $set         = new Collection;
        $repetitions = $repository->getAllBudgetLimitRepetitions($start, $end);


        while ($current < $end) {
            $currentStart = clone $current;
            $currentEnd   = Navigation::endOfPeriod($currentStart, $viewRange);
            $reps         = $repetitions->filter(
                function (LimitRepetition $repetition) use ($budget, $currentStart) {
                    if ($repetition->budget_id === $budget->id && $repetition->startdate == $currentStart) {
                        return true;
                    }

                    return false;
                }
            );
            $budgeted     = $reps->sum('amount');
            $spent        = $repository->spentInPeriod(new Collection([$budget]), $accounts, $currentStart, $currentEnd);
            $entry        = [
                'date'     => clone $currentStart,
                'budgeted' => $budgeted,
                'spent'    => $spent,
            ];
            $set->push($entry);
            $currentEnd->addDay();
            $current = clone $currentEnd;

        }
        $data = $this->generator->period($set, $viewRange);
        $cache->store($data);

        return Response::json($data);
    }

    /**
     * @param Collection $repetitions
     * @param Budget     $budget
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return Collection
     */
    private function filterRepetitions(Collection $repetitions, Budget $budget, Carbon $start, Carbon $end): Collection
    {

        return $repetitions->filter(
            function (LimitRepetition $repetition) use ($budget, $start, $end) {
                if ($repetition->startdate < $end && $repetition->enddate > $start && $repetition->budget_id === $budget->id) {
                    return true;
                }

                return false;
            }
        );
    }

    /**
     * @param BudgetRepositoryInterface $repository
     * @param Budget                    $budget
     * @param Collection                $repetitions
     *
     * @return Collection
     */
    private function spentInPeriodMulti(BudgetRepositoryInterface $repository, Budget $budget, Collection $repetitions): Collection
    {
        $format     = strval(trans('config.month_and_day'));
        $collection = new Collection;
        $name       = $budget->name;
        /** @var LimitRepetition $repetition */
        foreach ($repetitions as $repetition) {
            $expenses = $repository->spentInPeriod(new Collection([$budget]), new Collection, $repetition->startdate, $repetition->enddate);

            if ($repetitions->count() > 1) {
                $name = $budget->name . ' ' . trans(
                        'firefly.between_dates',
                        ['start' => $repetition->startdate->formatLocalized($format), 'end' => $repetition->enddate->formatLocalized($format)]
                    );
            }
            $amount    = $repetition->amount;
            $left      = bccomp(bcadd($amount, $expenses), '0') < 1 ? '0' : bcadd($amount, $expenses);
            $spent     = bccomp(bcadd($amount, $expenses), '0') < 1 ? bcmul($amount, '-1') : $expenses;
            $overspent = bccomp(bcadd($amount, $expenses), '0') < 1 ? bcadd($amount, $expenses) : '0';
            $array     = [$name, $left, $spent, $overspent, $amount, $spent];
            $collection->push($array);
        }

        return $collection;
    }

    /**
     * @param BudgetRepositoryInterface $repository
     * @param Budget                    $budget
     * @param Carbon                    $start
     * @param Carbon                    $end
     *
     * @return Collection
     */
    private function spentInPeriodSingle(BudgetRepositoryInterface $repository, Budget $budget, Carbon $start, Carbon $end): Collection
    {
        $collection = new Collection;
        $amount     = '0';
        $left       = '0';
        $spent      = $repository->spentInPeriod(new Collection([$budget]), new Collection, $start, $end);
        $overspent  = '0';
        $array      = [$budget->name, $left, $spent, $overspent, $amount, $spent];
        $collection->push($array);

        return $collection;
    }

    /**
     * @param BudgetRepositoryInterface $repository
     * @param Carbon                    $start
     * @param Carbon                    $end
     *
     * @return array
     */
    private function spentInPeriodWithout(BudgetRepositoryInterface $repository, Carbon $start, Carbon $end):array
    {
        $list = $repository->journalsInPeriodWithoutBudget(new Collection, $start, $end); // budget
        $sum  = '0';
        /** @var TransactionJournal $entry */
        foreach ($list as $entry) {
            $sum = bcadd(TransactionJournal::amount($entry), $sum);
        }

        return [trans('firefly.no_budget'), '0', '0', $sum, '0', '0'];
    }
}
