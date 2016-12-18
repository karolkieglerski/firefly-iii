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

namespace FireflyIII\Http\Controllers;

use Amount;
use Carbon\Carbon;
use Config;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Helpers\Collector\JournalCollector;
use FireflyIII\Http\Requests\BudgetFormRequest;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\Budget;
use FireflyIII\Models\LimitRepetition;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use Illuminate\Support\Collection;
use Input;
use Log;
use Navigation;
use Preferences;
use Response;
use Session;
use URL;
use View;

/**
 * Class BudgetController
 *
 * @package FireflyIII\Http\Controllers
 */
class BudgetController extends Controller
{

    /**
     *
     */
    public function __construct()
    {
        parent::__construct();

        View::share('hideBudgets', true);

        $this->middleware(
            function ($request, $next) {
                View::share('title', trans('firefly.budgets'));
                View::share('mainTitleIcon', 'fa-tasks');

                return $next($request);
            }
        );
    }

    /**
     * @param BudgetRepositoryInterface $repository
     * @param Budget                    $budget
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function amount(BudgetRepositoryInterface $repository, Budget $budget)
    {
        $amount = intval(Input::get('amount'));
        /** @var Carbon $start */
        $start = session('start', Carbon::now()->startOfMonth());
        /** @var Carbon $end */
        $end       = session('end', Carbon::now()->endOfMonth());
        $viewRange = Preferences::get('viewRange', '1M')->data;

        // is custom view range?
        if (session('is_custom_range') === true) {
            $viewRange = 'custom';
        }

        $limitRepetition = $repository->updateLimitAmount($budget, $start, $end, $viewRange, $amount);
        if ($amount == 0) {
            $limitRepetition = null;
        }
        Preferences::mark();

        return Response::json(['name' => $budget->name, 'repetition' => $limitRepetition ? $limitRepetition->id : 0]);

    }

    /**
     * @return View
     */
    public function create()
    {
        // put previous url in session if not redirect from store (not "create another").
        if (session('budgets.create.fromStore') !== true) {
            Session::put('budgets.create.url', URL::previous());
        }
        Session::forget('budgets.create.fromStore');
        Session::flash('gaEventCategory', 'budgets');
        Session::flash('gaEventAction', 'create');
        $subTitle = (string)trans('firefly.create_new_budget');

        return view('budgets.create', compact('subTitle'));
    }

    /**
     * @param Budget $budget
     *
     * @return View
     */
    public function delete(Budget $budget)
    {
        $subTitle = trans('firefly.delete_budget', ['name' => $budget->name]);

        // put previous url in session
        Session::put('budgets.delete.url', URL::previous());
        Session::flash('gaEventCategory', 'budgets');
        Session::flash('gaEventAction', 'delete');

        return view('budgets.delete', compact('budget', 'subTitle'));
    }

    /**
     * @param Budget                    $budget
     * @param BudgetRepositoryInterface $repository
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Budget $budget, BudgetRepositoryInterface $repository)
    {

        $name = $budget->name;
        $repository->destroy($budget);


        Session::flash('success', strval(trans('firefly.deleted_budget', ['name' => e($name)])));
        Preferences::mark();


        return redirect(session('budgets.delete.url'));
    }

    /**
     * @param Budget $budget
     *
     * @return View
     */
    public function edit(Budget $budget)
    {
        $subTitle = trans('firefly.edit_budget', ['name' => $budget->name]);

        // put previous url in session if not redirect from store (not "return_to_edit").
        if (session('budgets.edit.fromUpdate') !== true) {
            Session::put('budgets.edit.url', URL::previous());
        }
        Session::forget('budgets.edit.fromUpdate');
        Session::flash('gaEventCategory', 'budgets');
        Session::flash('gaEventAction', 'edit');

        return view('budgets.edit', compact('budget', 'subTitle'));

    }

    /**
     * @param BudgetRepositoryInterface  $repository
     * @param AccountRepositoryInterface $accountRepository
     *
     * @return View
     *
     */
    public function index(BudgetRepositoryInterface $repository, AccountRepositoryInterface $accountRepository)
    {
        $repository->cleanupBudgets();

        $budgets    = $repository->getActiveBudgets();
        $inactive   = $repository->getInactiveBudgets();
        $spent      = '0';
        $budgeted   = '0';
        $range      = Preferences::get('viewRange', '1M')->data;
        $repeatFreq = Config::get('firefly.range_to_repeat_freq.' . $range);

        if (session('is_custom_range') === true) {
            $repeatFreq = 'custom';
        }

        /** @var Carbon $start */
        $start = session('start', new Carbon);
        /** @var Carbon $end */
        $end               = session('end', new Carbon);
        $key               = 'budgetIncomeTotal' . $start->format('Ymd') . $end->format('Ymd');
        $budgetIncomeTotal = Preferences::get($key, 1000)->data;
        $period            = Navigation::periodShow($start, $range);
        $periodStart       = $start->formatLocalized($this->monthAndDayFormat);
        $periodEnd         = $end->formatLocalized($this->monthAndDayFormat);
        $accounts          = $accountRepository->getAccountsByType([AccountType::DEFAULT, AccountType::ASSET, AccountType::CASH]);
        $startAsString     = $start->format('Y-m-d');
        $endAsString       = $end->format('Y-m-d');
        Log::debug('Now at /budgets');

        // loop the budgets:
        /** @var Budget $budget */
        foreach ($budgets as $budget) {
            Log::debug(sprintf('Now at budget #%d ("%s")', $budget->id, $budget->name));
            $budget->spent    = $repository->spentInPeriod(new Collection([$budget]), $accounts, $start, $end);
            $allRepetitions   = $repository->getAllBudgetLimitRepetitions($start, $end);
            $otherRepetitions = new Collection;

            /** @var LimitRepetition $repetition */
            foreach ($allRepetitions as $repetition) {
                if ($repetition->budget_id == $budget->id) {
                    if ($repetition->budgetLimit->repeat_freq == $repeatFreq
                        && $repetition->startdate->format('Y-m-d') == $startAsString
                        && $repetition->enddate->format('Y-m-d') == $endAsString
                    ) {
                        // do something
                        $budget->currentRep = $repetition;
                        continue;
                    }
                    $otherRepetitions->push($repetition);
                }
            }
            $budget->otherRepetitions = $otherRepetitions;

            if (!is_null($budget->currentRep) && !is_null($budget->currentRep->id)) {
                $budgeted = bcadd($budgeted, $budget->currentRep->amount);
            }
            $spent = bcadd($spent, $budget->spent);

        }


        $defaultCurrency = Amount::getDefaultCurrency();

        return view(
            'budgets.index', compact(
                               'periodStart', 'periodEnd',
                               'period', 'range', 'budgetIncomeTotal',
                               'defaultCurrency', 'inactive', 'budgets',
                               'spent', 'budgeted'
                           )
        );
    }

    /**
     * @return View
     */
    public function noBudget()
    {
        /** @var Carbon $start */
        $start = session('start', Carbon::now()->startOfMonth());
        /** @var Carbon $end */
        $end      = session('end', Carbon::now()->endOfMonth());
        $page     = intval(Input::get('page')) == 0 ? 1 : intval(Input::get('page'));
        $pageSize = intval(Preferences::get('transactionPageSize', 50)->data);
        $subTitle = trans(
            'firefly.without_budget_between',
            ['start' => $start->formatLocalized($this->monthAndDayFormat), 'end' => $end->formatLocalized($this->monthAndDayFormat)]
        );

        // collector
        $collector = new JournalCollector(auth()->user());
        $collector->setAllAssetAccounts()->setRange($start, $end)->setLimit($pageSize)->setPage($page)->withoutBudget();
        $journals = $collector->getPaginatedJournals();
        $journals->setPath('/budgets/list/noBudget');

        return view('budgets.no-budget', compact('journals', 'subTitle'));
    }

    /**
     * @return \Illuminate\Http\RedirectResponse
     */
    public function postUpdateIncome()
    {
        $range = Preferences::get('viewRange', '1M')->data;
        /** @var Carbon $date */
        $date  = session('start', new Carbon);
        $start = Navigation::startOfPeriod($date, $range);
        $end   = Navigation::endOfPeriod($start, $range);
        $key   = 'budgetIncomeTotal' . $start->format('Ymd') . $end->format('Ymd');

        Preferences::set($key, intval(Input::get('amount')));
        Preferences::mark();

        return redirect(route('budgets.index'));
    }

    /**
     * @param BudgetRepositoryInterface  $repository
     * @param AccountRepositoryInterface $accountRepository
     * @param Budget                     $budget
     *
     * @return View
     */
    public function show(BudgetRepositoryInterface $repository, AccountRepositoryInterface $accountRepository, Budget $budget)
    {
        /** @var Carbon $start */
        $start      = session('first', Carbon::create()->startOfYear());
        $end        = new Carbon;
        $page       = intval(Input::get('page')) == 0 ? 1 : intval(Input::get('page'));
        $pageSize   = intval(Preferences::get('transactionPageSize', 50)->data);
        $accounts   = $accountRepository->getAccountsByType([AccountType::DEFAULT, AccountType::ASSET, AccountType::CASH]);
        $repetition = null;
        // collector:
        $collector = new JournalCollector(auth()->user());
        $collector->setAllAssetAccounts()->setRange($start, $end)->setBudget($budget)->setLimit($pageSize)->setPage($page);
        $journals = $collector->getPaginatedJournals();
        $journals->setPath('/budgets/show/' . $budget->id);


        $set      = $budget->limitrepetitions()->orderBy('startdate', 'DESC')->get();
        $subTitle = e($budget->name);
        $limits   = new Collection();

        /** @var LimitRepetition $entry */
        foreach ($set as $entry) {
            $entry->spent = $repository->spentInPeriod(new Collection([$budget]), $accounts, $entry->startdate, $entry->enddate);
            $limits->push($entry);
        }

        return view('budgets.show', compact('limits', 'budget', 'repetition', 'journals', 'subTitle'));
    }

    /**
     * @param Budget          $budget
     * @param LimitRepetition $repetition
     *
     * @return View
     * @throws FireflyException
     */
    public function showByRepetition(Budget $budget, LimitRepetition $repetition)
    {
        if ($repetition->budgetLimit->budget->id != $budget->id) {
            throw new FireflyException('This budget limit is not part of this budget.');
        }

        /** @var BudgetRepositoryInterface $repository */
        $repository = app(BudgetRepositoryInterface::class);
        /** @var AccountRepositoryInterface $accountRepository */
        $accountRepository = app(AccountRepositoryInterface::class);
        $start             = $repetition->startdate;
        $end               = $repetition->enddate;
        $page              = intval(Input::get('page')) == 0 ? 1 : intval(Input::get('page'));
        $pageSize          = intval(Preferences::get('transactionPageSize', 50)->data);
        $subTitle          = trans(
            'firefly.budget_in_month', ['name' => $budget->name, 'month' => $repetition->startdate->formatLocalized($this->monthFormat)]
        );
        $accounts          = $accountRepository->getAccountsByType([AccountType::DEFAULT, AccountType::ASSET, AccountType::CASH]);


        // collector:
        $collector = new JournalCollector(auth()->user());
        $collector->setAllAssetAccounts()->setRange($start, $end)->setBudget($budget)->setLimit($pageSize)->setPage($page);
        $journals = $collector->getPaginatedJournals();
        $journals->setPath('/budgets/show/' . $budget->id . '/' . $repetition->id);


        $repetition->spent = $repository->spentInPeriod(new Collection([$budget]), $accounts, $repetition->startdate, $repetition->enddate);
        $limits            = new Collection([$repetition]);

        return view('budgets.show', compact('limits', 'budget', 'repetition', 'journals', 'subTitle'));

    }

    /**
     * @param BudgetFormRequest         $request
     * @param BudgetRepositoryInterface $repository
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(BudgetFormRequest $request, BudgetRepositoryInterface $repository)
    {
        $data   = $request->getBudgetData();
        $budget = $repository->store($data);

        Session::flash('success', strval(trans('firefly.stored_new_budget', ['name' => e($budget->name)])));
        Preferences::mark();

        if (intval(Input::get('create_another')) === 1) {
            // set value so create routine will not overwrite URL:
            Session::put('budgets.create.fromStore', true);

            return redirect(route('budgets.create'))->withInput();
        }

        // redirect to previous URL.
        return redirect(session('budgets.create.url'));

    }

    /**
     * @param BudgetFormRequest         $request
     * @param BudgetRepositoryInterface $repository
     * @param Budget                    $budget
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(BudgetFormRequest $request, BudgetRepositoryInterface $repository, Budget $budget)
    {
        $data = $request->getBudgetData();
        $repository->update($budget, $data);

        Session::flash('success', strval(trans('firefly.updated_budget', ['name' => e($budget->name)])));
        Preferences::mark();

        if (intval(Input::get('return_to_edit')) === 1) {
            // set value so edit routine will not overwrite URL:
            Session::put('budgets.edit.fromUpdate', true);

            return redirect(route('budgets.edit', [$budget->id]))->withInput(['return_to_edit' => 1]);
        }

        // redirect to previous URL.
        return redirect(session('budgets.edit.url'));

    }

    /**
     * @return View
     */
    public function updateIncome()
    {
        $range  = Preferences::get('viewRange', '1M')->data;
        $format = strval(trans('config.month_and_day'));

        /** @var Carbon $date */
        $date         = session('start', new Carbon);
        $start        = Navigation::startOfPeriod($date, $range);
        $end          = Navigation::endOfPeriod($start, $range);
        $key          = 'budgetIncomeTotal' . $start->format('Ymd') . $end->format('Ymd');
        $amount       = Preferences::get($key, 1000);
        $displayStart = $start->formatLocalized($format);
        $displayEnd   = $end->formatLocalized($format);

        return view('budgets.income', compact('amount', 'displayStart', 'displayEnd'));
    }

}
