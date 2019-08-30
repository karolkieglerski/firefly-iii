<?php
/**
 * BudgetRepository.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
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

namespace FireflyIII\Repositories\Budget;

use Carbon\Carbon;
use Exception;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Factory\TransactionCurrencyFactory;
use FireflyIII\Helpers\Collector\GroupCollectorInterface;
use FireflyIII\Models\AvailableBudget;
use FireflyIII\Models\Budget;
use FireflyIII\Models\BudgetLimit;
use FireflyIII\Models\RuleAction;
use FireflyIII\Models\RuleTrigger;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Models\TransactionType;
use FireflyIII\Services\Internal\Destroy\BudgetDestroyService;
use FireflyIII\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Log;
use Navigation;

/**
 * Class BudgetRepository.
 *
 */
class BudgetRepository implements BudgetRepositoryInterface
{
    /** @var User */
    private $user;

    /**
     * Constructor.
     */
    public function __construct()
    {
        if ('testing' === config('app.env')) {
            Log::warning(sprintf('%s should not be instantiated in the TEST environment!', get_class($this)));
        }
    }

    /**
     * @return bool
     *  // it's 5.
     */
    public function cleanupBudgets(): bool
    {
        // delete limits with amount 0:
        try {
            BudgetLimit::where('amount', 0)->delete();
        } catch (Exception $e) {
            Log::debug(sprintf('Could not delete budget limit: %s', $e->getMessage()));
        }
        Budget::where('order', 0)->update(['order' => 100]);

        // do the clean up by hand because Sqlite can be tricky with this.
        $budgetLimits = BudgetLimit::orderBy('created_at', 'DESC')->get(['id', 'budget_id', 'start_date', 'end_date']);
        $count        = [];
        /** @var BudgetLimit $budgetLimit */
        foreach ($budgetLimits as $budgetLimit) {
            $key = $budgetLimit->budget_id . '-' . $budgetLimit->start_date->format('Y-m-d') . $budgetLimit->end_date->format('Y-m-d');
            if (isset($count[$key])) {
                // delete it!
                try {
                    BudgetLimit::find($budgetLimit->id)->delete();
                } catch (Exception $e) {
                    Log::debug(sprintf('Could not delete budget limit: %s', $e->getMessage()));
                }
            }
            $count[$key] = true;
        }

        return true;
    }

    /**
     * @param Budget $budget
     *
     * @return bool
     */
    public function destroy(Budget $budget): bool
    {
        /** @var BudgetDestroyService $service */
        $service = app(BudgetDestroyService::class);
        $service->destroy($budget);

        return true;
    }

    /**
     * @param int|null    $budgetId
     * @param string|null $budgetName
     *
     * @return Budget|null
     */
    public function findBudget(?int $budgetId, ?string $budgetName): ?Budget
    {
        Log::debug('Now in findBudget()');
        Log::debug(sprintf('Searching for budget with ID #%d...', $budgetId));
        $result = $this->findNull((int)$budgetId);
        if (null === $result && null !== $budgetName && '' !== $budgetName) {
            Log::debug(sprintf('Searching for budget with name %s...', $budgetName));
            $result = $this->findByName((string)$budgetName);
        }
        if (null !== $result) {
            Log::debug(sprintf('Found budget #%d: %s', $result->id, $result->name));
        }
        Log::debug(sprintf('Found result is null? %s', var_export(null === $result, true)));

        return $result;
    }

    /**
     * Find budget by name.
     *
     * @param string|null $name
     *
     * @return Budget|null
     */
    public function findByName(?string $name): ?Budget
    {
        if (null === $name) {
            return null;
        }
        $query = sprintf('%%%s%%', $name);

        return $this->user->budgets()->where('name', 'LIKE', $query)->first();
    }

    /**
     * Find a budget or return NULL
     *
     * @param int $budgetId |null
     *
     * @return Budget|null
     */
    public function findNull(int $budgetId = null): ?Budget
    {
        if (null === $budgetId) {
            return null;
        }

        return $this->user->budgets()->find($budgetId);
    }

    /**
     * This method returns the oldest journal or transaction date known to this budget.
     * Will cache result.
     *
     * @param Budget $budget
     *
     * @return Carbon
     *
     */
    public function firstUseDate(Budget $budget): ?Carbon
    {
        $oldest  = null;
        $journal = $budget->transactionJournals()->orderBy('date', 'ASC')->first();
        if (null !== $journal) {
            $oldest = $journal->date < $oldest ? $journal->date : $oldest;
        }

        $transaction = $budget
            ->transactions()
            ->leftJoin('transaction_journals', 'transaction_journals.id', '=', 'transactions.id')
            ->orderBy('transaction_journals.date', 'ASC')->first(['transactions.*', 'transaction_journals.date']);
        if (null !== $transaction) {
            $carbon = new Carbon($transaction->date);
            $oldest = $carbon < $oldest ? $carbon : $oldest;
        }

        return $oldest;
    }

    /**
     * @return Collection
     */
    public function getActiveBudgets(): Collection
    {
        /** @var Collection $set */
        $set = $this->user->budgets()->where('active', 1)
                          ->orderBy('order', 'DESC')
                          ->orderBy('name', 'ASC')
                          ->get();

        return $set;
    }

    /**
     * Returns all available budget objects.
     *
     * @param TransactionCurrency $currency
     *
     * @return Collection
     */
    public function getAvailableBudgetsByCurrency(TransactionCurrency $currency): Collection
    {
        return $this->user->availableBudgets()->where('transaction_currency_id', $currency->id)->get();
    }

    /**
     * Returns all available budget objects.
     *
     * @param Carbon|null $start
     * @param Carbon|null $end
     *
     * @return Collection
     *
     */
    public function getAvailableBudgetsByDate(?Carbon $start, ?Carbon $end): Collection
    {
        $query = $this->user->availableBudgets();

        if (null !== $start) {
            $query->where('start_date', '>=', $start->format('Y-m-d H:i:s'));
        }
        if (null !== $end) {
            $query->where('end_date', '<=', $end->format('Y-m-d H:i:s'));
        }

        return $query->get();
    }

    /**
     * @param Budget $budget
     * @param Carbon $start
     * @param Carbon $end
     *
     * @return Collection
     *
     */
    public function getBudgetLimits(Budget $budget, Carbon $start = null, Carbon $end = null): Collection
    {
        if (null === $end && null === $start) {
            return $budget->budgetlimits()->with(['transactionCurrency'])->orderBy('budget_limits.start_date', 'DESC')->get(['budget_limits.*']);
        }
        if (null === $end xor null === $start) {
            $query = $budget->budgetlimits()->with(['transactionCurrency'])->orderBy('budget_limits.start_date', 'DESC');
            // one of the two is null
            if (null !== $end) {
                // end date must be before $end.
                $query->where('end_date', '<=', $end->format('Y-m-d 00:00:00'));
            }
            if (null !== $start) {
                // start date must be after $start.
                $query->where('start_date', '>=', $start->format('Y-m-d 00:00:00'));
            }
            $set = $query->get(['budget_limits.*']);

            return $set;
        }

        // when both dates are set:
        $set = $budget->budgetlimits()
                      ->where(
                          function (Builder $q5) use ($start, $end) {
                              $q5->where(
                                  function (Builder $q1) use ($start, $end) {
                                      // budget limit ends within period
                                      $q1->where(
                                          function (Builder $q2) use ($start, $end) {
                                              $q2->where('budget_limits.end_date', '>=', $start->format('Y-m-d 00:00:00'));
                                              $q2->where('budget_limits.end_date', '<=', $end->format('Y-m-d 00:00:00'));
                                          }
                                      )
                                          // budget limit start within period
                                         ->orWhere(
                                              function (Builder $q3) use ($start, $end) {
                                                  $q3->where('budget_limits.start_date', '>=', $start->format('Y-m-d 00:00:00'));
                                                  $q3->where('budget_limits.start_date', '<=', $end->format('Y-m-d 00:00:00'));
                                              }
                                          );
                                  }
                              )
                                 ->orWhere(
                                     function (Builder $q4) use ($start, $end) {
                                         // or start is before start AND end is after end.
                                         $q4->where('budget_limits.start_date', '<=', $start->format('Y-m-d 00:00:00'));
                                         $q4->where('budget_limits.end_date', '>=', $end->format('Y-m-d 00:00:00'));
                                     }
                                 );
                          }
                      )->orderBy('budget_limits.start_date', 'DESC')->get(['budget_limits.*']);

        return $set;
    }

    /**
     * This method is being used to generate the budget overview in the year/multi-year report. Its used
     * in both the year/multi-year budget overview AND in the accompanying chart.
     *
     * @param Collection $budgets
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return array
     */
    public function getBudgetPeriodReport(Collection $budgets, Collection $accounts, Carbon $start, Carbon $end): array
    {
        $carbonFormat = Navigation::preferredCarbonFormat($start, $end);
        $data         = [];


        // get all transactions:
        /** @var GroupCollectorInterface $collector */
        $collector = app(GroupCollectorInterface::class);
        $collector->setAccounts($accounts)->setRange($start, $end);
        $collector->setBudgets($budgets);
        $journals = $collector->getExtractedJournals();

        // loop transactions:
        /** @var array $journal */
        foreach ($journals as $journal) {
            // prep data array for currency:
            $budgetId   = (int)$journal['budget_id'];
            $budgetName = $journal['budget_name'];
            $currencyId = (int)$journal['currency_id'];
            $key        = sprintf('%d-%d', $budgetId, $currencyId);

            $data[$key]                   = $data[$key] ?? [
                    'id'                      => $budgetId,
                    'name'                    => sprintf('%s (%s)', $budgetName, $journal['currency_name']),
                    'sum'                     => '0',
                    'currency_id'             => $currencyId,
                    'currency_code'           => $journal['currency_code'],
                    'currency_name'           => $journal['currency_name'],
                    'currency_symbol'         => $journal['currency_symbol'],
                    'currency_decimal_places' => $journal['currency_decimal_places'],
                    'entries'                 => [],
                ];
            $date                         = $journal['date']->format($carbonFormat);
            $data[$key]['entries'][$date] = bcadd($data[$budgetId]['entries'][$date] ?? '0', $journal['amount']);
        }

        return $data;
    }

    /**
     * @return Collection
     */
    public function getBudgets(): Collection
    {
        /** @var Collection $set */
        $set = $this->user->budgets()->orderBy('order', 'DESC')
                          ->orderBy('name', 'ASC')->get();

        return $set;
    }

    /**
     * Get all budgets with these ID's.
     *
     * @param array $budgetIds
     *
     * @return Collection
     */
    public function getByIds(array $budgetIds): Collection
    {
        return $this->user->budgets()->whereIn('id', $budgetIds)->get();
    }

    /**
     * @return Collection
     */
    public function getInactiveBudgets(): Collection
    {
        /** @var Collection $set */
        $set = $this->user->budgets()->orderBy('order', 'DESC')
                          ->orderBy('name', 'ASC')->where('active', 0)->get();

        return $set;
    }

    /**
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return array
     */
    public function getNoBudgetPeriodReport(Collection $accounts, Carbon $start, Carbon $end): array
    {
        $carbonFormat = Navigation::preferredCarbonFormat($start, $end);

        /** @var GroupCollectorInterface $collector */
        $collector = app(GroupCollectorInterface::class);

        $collector->setAccounts($accounts)->setRange($start, $end);
        $collector->setTypes([TransactionType::WITHDRAWAL]);
        $collector->withoutBudget();
        $journals = $collector->getExtractedJournals();
        $data     = [];

        /** @var array $journal */
        foreach ($journals as $journal) {
            $currencyId = (int)$journal['currency_id'];

            $data[$currencyId] = $data[$currencyId] ?? [
                    'id'                      => 0,
                    'name'                    => sprintf('%s (%s)', trans('firefly.no_budget'), $journal['currency_name']),
                    'sum'                     => '0',
                    'currency_id'             => $currencyId,
                    'currency_code'           => $journal['currency_code'],
                    'currency_name'           => $journal['currency_name'],
                    'currency_symbol'         => $journal['currency_symbol'],
                    'currency_decimal_places' => $journal['currency_decimal_places'],
                    'entries'                 => [],
                ];
            $date              = $journal['date']->format($carbonFormat);

            if (!isset($data[$currencyId]['entries'][$date])) {
                $data[$currencyId]['entries'][$date] = '0';
            }
            $data[$currencyId]['entries'][$date] = bcadd($data[$currencyId]['entries'][$date], $journal['amount']);
        }

        return $data;
    }

    /**
     * @param string $query
     *
     * @return Collection
     */
    public function searchBudget(string $query): Collection
    {

        $search = $this->user->budgets();
        if ('' !== $query) {
            $search->where('name', 'LIKE', sprintf('%%%s%%', $query));
        }

        return $search->get();
    }

    /**
     * @param TransactionCurrency $currency
     * @param Carbon              $start
     * @param Carbon              $end
     * @param string              $amount
     *
     * @return AvailableBudget
     */
    public function setAvailableBudget(TransactionCurrency $currency, Carbon $start, Carbon $end, string $amount): AvailableBudget
    {
        $availableBudget = $this->user->availableBudgets()
                                      ->where('transaction_currency_id', $currency->id)
                                      ->where('start_date', $start->format('Y-m-d 00:00:00'))
                                      ->where('end_date', $end->format('Y-m-d 00:00:00'))->first();
        if (null === $availableBudget) {
            $availableBudget = new AvailableBudget;
            $availableBudget->user()->associate($this->user);
            $availableBudget->transactionCurrency()->associate($currency);
            $availableBudget->start_date = $start->format('Y-m-d 00:00:00');
            $availableBudget->end_date   = $end->format('Y-m-d 00:00:00');
        }
        $availableBudget->amount = $amount;
        $availableBudget->save();

        return $availableBudget;
    }

    /**
     * @param Budget $budget
     * @param int    $order
     */
    public function setBudgetOrder(Budget $budget, int $order): void
    {
        $budget->order = $order;
        $budget->save();
    }

    /**
     * @param User $user
     */
    public function setUser(User $user): void
    {
        $this->user = $user;
    }

    /**
     * @param Collection $budgets
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     * TODO refactor me.
     *
     * @return array
     */
    public function spentInPeriodMc(Collection $budgets, Collection $accounts, Carbon $start, Carbon $end): array
    {
        /** @var GroupCollectorInterface $collector */
        $collector = app(GroupCollectorInterface::class);
        $collector->setUser($this->user);
        $collector->setRange($start, $end)->setBudgets($budgets)->withBudgetInformation();

        if ($accounts->count() > 0) {
            $collector->setAccounts($accounts);
        }
        // TODO possible candidate for getExtractedGroups
        $set        = $collector->getGroups();
        $return     = [];
        $total      = [];
        $currencies = [];
        /** @var array $group */
        foreach ($set as $group) {
            /** @var array $transaction */
            foreach ($group['transactions'] as $transaction) {
                $code = $transaction['currency_code'];
                if (!isset($currencies[$code])) {
                    $currencies[$code] = [
                        'id'             => $transaction['currency_id'],
                        'decimal_places' => $transaction['currency_decimal_places'],
                        'code'           => $transaction['currency_code'],
                        'name'           => $transaction['currency_name'],
                        'symbol'         => $transaction['currency_symbol'],
                    ];
                }
                $total[$code] = isset($total[$code]) ? bcadd($total[$code], $transaction['amount']) : $transaction['amount'];
            }
        }
        /**
         * @var string $code
         * @var string $spent
         */
        foreach ($total as $code => $spent) {
            /** @var TransactionCurrency $currency */
            $currency = $currencies[$code];
            $return[] = [
                'currency_id'             => $currency['id'],
                'currency_code'           => $code,
                'currency_name'           => $currency['name'],
                'currency_symbol'         => $currency['symbol'],
                'currency_decimal_places' => $currency['decimal_places'],
                'amount'                  => $spent,
            ];
        }

        return $return;
    }

    /**
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return string
     */
    public function spentInPeriodWoBudget(Collection $accounts, Carbon $start, Carbon $end): string
    {
        /** @var GroupCollectorInterface $collector */
        $collector = app(GroupCollectorInterface::class);
        $collector->setUser($this->user);
        $collector->setRange($start, $end)->setTypes([TransactionType::WITHDRAWAL])
                  ->withoutBudget();

        if ($accounts->count() > 0) {
            $collector->setAccounts($accounts);
        }

        return $collector->getSum();
    }

    /**
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return array
     */
    public function spentInPeriodWoBudgetMc(Collection $accounts, Carbon $start, Carbon $end): array
    {
        /** @var GroupCollectorInterface $collector */
        $collector = app(GroupCollectorInterface::class);

        $collector->setUser($this->user);
        $collector->setRange($start, $end)->setTypes([TransactionType::WITHDRAWAL])->withoutBudget();

        if ($accounts->count() > 0) {
            $collector->setAccounts($accounts);
        }
        $journals   = $collector->getExtractedJournals();
        $return     = [];
        $total      = [];
        $currencies = [];
        /** @var array $journal */
        foreach ($journals as $journal) {
            $code = $journal['currency_code'];
            if (!isset($currencies[$code])) {
                $currencies[$code] = [
                    'id'             => $journal['currency_id'],
                    'name'           => $journal['currency_name'],
                    'symbol'         => $journal['currency_symbol'],
                    'decimal_places' => $journal['currency_decimal_places'],
                ];
            }
            $total[$code] = isset($total[$code]) ? bcadd($total[$code], $journal['amount']) : $journal['amount'];
        }
        foreach ($total as $code => $spent) {
            /** @var TransactionCurrency $currency */
            $currency = $currencies[$code];
            $return[] = [
                'currency_id'             => $currency['id'],
                'currency_code'           => $code,
                'currency_name'           => $currency['name'],
                'currency_symbol'         => $currency['symbol'],
                'currency_decimal_places' => $currency['decimal_places'],
                'amount'                  => $spent,
            ];
        }

        return $return;
    }

    /**
     * @param array $data
     *
     * @return Budget
     */
    public function store(array $data): Budget
    {
        $newBudget = new Budget(
            [
                'user_id' => $this->user->id,
                'name'    => $data['name'],
            ]
        );
        $newBudget->save();

        return $newBudget;
    }

    /**
     * @param array $data
     *
     * @return BudgetLimit
     */
    public function storeBudgetLimit(array $data): BudgetLimit
    {
        $this->cleanupBudgets();
        /** @var Budget $budget */
        $budget = $data['budget'];

        // if no currency has been provided, use the user's default currency:
        /** @var TransactionCurrencyFactory $factory */
        $factory  = app(TransactionCurrencyFactory::class);
        $currency = $factory->find($data['currency_id'] ?? null, $data['currency_code'] ?? null);
        if (null === $currency) {
            $currency = app('amount')->getDefaultCurrencyByUser($this->user);
        }

        // find limit with same date range.
        // if it exists, return that one.
        $limit = $budget->budgetlimits()
                        ->where('budget_limits.start_date', $data['start']->format('Y-m-d 00:00:00'))
                        ->where('budget_limits.end_date', $data['end']->format('Y-m-d 00:00:00'))
                        ->where('budget_limits.transaction_currency_id', $currency->id)
                        ->get(['budget_limits.*'])->first();
        if (null !== $limit) {
            return $limit;
        }
        Log::debug('No existing budget limit, create a new one');

        // or create one and return it.
        $limit = new BudgetLimit;
        $limit->budget()->associate($budget);
        $limit->start_date              = $data['start']->format('Y-m-d 00:00:00');
        $limit->end_date                = $data['end']->format('Y-m-d 00:00:00');
        $limit->amount                  = $data['amount'];
        $limit->transaction_currency_id = $currency->id;
        $limit->save();
        Log::debug(sprintf('Created new budget limit with ID #%d and amount %s', $limit->id, $data['amount']));

        return $limit;
    }

    /**
     * @param Budget $budget
     * @param array  $data
     *
     * @return Budget
     */
    public function update(Budget $budget, array $data): Budget
    {
        $oldName        = $budget->name;
        $budget->name   = $data['name'];
        $budget->active = $data['active'];
        $budget->save();
        $this->updateRuleTriggers($oldName, $data['name']);
        $this->updateRuleActions($oldName, $data['name']);
        app('preferences')->mark();

        return $budget;
    }

    /**
     * TODO only used in the API.
     *
     * @param AvailableBudget $availableBudget
     * @param array           $data
     *
     * @return AvailableBudget
     * @throws FireflyException
     */
    public function updateAvailableBudget(AvailableBudget $availableBudget, array $data): AvailableBudget
    {
        $existing = $this->user->availableBudgets()
                               ->where('transaction_currency_id', $data['currency_id'])
                               ->where('start_date', $data['start']->format('Y-m-d 00:00:00'))
                               ->where('end_date', $data['end']->format('Y-m-d 00:00:00'))
                               ->where('id', '!=', $availableBudget->id)
                               ->first();

        if (null !== $existing) {
            throw new FireflyException(sprintf('An entry already exists for these parameters: available budget object with ID #%d', $existing->id));
        }
        $availableBudget->transaction_currency_id = $data['currency_id'];
        $availableBudget->start_date              = $data['start'];
        $availableBudget->end_date                = $data['end'];
        $availableBudget->amount                  = $data['amount'];
        $availableBudget->save();

        return $availableBudget;

    }

    /**
     * @param BudgetLimit $budgetLimit
     * @param array       $data
     *
     * @return BudgetLimit
     * @throws Exception
     */
    public function updateBudgetLimit(BudgetLimit $budgetLimit, array $data): BudgetLimit
    {
        $this->cleanupBudgets();
        /** @var Budget $budget */
        $budget = $data['budget'];

        $budgetLimit->budget()->associate($budget);
        $budgetLimit->start_date = $data['start']->format('Y-m-d 00:00:00');
        $budgetLimit->end_date   = $data['end']->format('Y-m-d 00:00:00');
        $budgetLimit->amount     = $data['amount'];

        // if no currency has been provided, use the user's default currency:
        /** @var TransactionCurrencyFactory $factory */
        $factory  = app(TransactionCurrencyFactory::class);
        $currency = $factory->find($data['currency_id'] ?? null, $data['currency_code'] ?? null);
        if (null === $currency) {
            $currency = app('amount')->getDefaultCurrencyByUser($this->user);
        }
        $currency->enabled = true;
        $currency->save();
        $budgetLimit->transaction_currency_id = $currency->id;

        $budgetLimit->save();
        Log::debug(sprintf('Updated budget limit with ID #%d and amount %s', $budgetLimit->id, $data['amount']));

        return $budgetLimit;
    }

    /**
     * @param Budget $budget
     * @param Carbon $start
     * @param Carbon $end
     * @param string $amount
     *
     * @return BudgetLimit|null
     *
     */
    public function updateLimitAmount(Budget $budget, Carbon $start, Carbon $end, string $amount): ?BudgetLimit
    {
        $this->cleanupBudgets();
        // count the limits:
        $limits = $budget->budgetlimits()
                         ->where('budget_limits.start_date', $start->format('Y-m-d 00:00:00'))
                         ->where('budget_limits.end_date', $end->format('Y-m-d 00:00:00'))
                         ->get(['budget_limits.*'])->count();
        Log::debug(sprintf('Found %d budget limits.', $limits));

        // there might be a budget limit for these dates:
        /** @var BudgetLimit $limit */
        $limit = $budget->budgetlimits()
                        ->where('budget_limits.start_date', $start->format('Y-m-d 00:00:00'))
                        ->where('budget_limits.end_date', $end->format('Y-m-d 00:00:00'))
                        ->first(['budget_limits.*']);

        // if more than 1 limit found, delete the others:
        if ($limits > 1 && null !== $limit) {
            Log::debug(sprintf('Found more than 1, delete all except #%d', $limit->id));
            $budget->budgetlimits()
                   ->where('budget_limits.start_date', $start->format('Y-m-d 00:00:00'))
                   ->where('budget_limits.end_date', $end->format('Y-m-d 00:00:00'))
                   ->where('budget_limits.id', '!=', $limit->id)->delete();
        }

        // delete if amount is zero.
        // Returns 0 if the two operands are equal,
        // 1 if the left_operand is larger than the right_operand, -1 otherwise.
        if (null !== $limit && bccomp($amount, '0') <= 0) {
            Log::debug(sprintf('%s is zero, delete budget limit #%d', $amount, $limit->id));
            try {
                $limit->delete();
            } catch (Exception $e) {
                Log::debug(sprintf('Could not delete limit: %s', $e->getMessage()));
            }


            return null;
        }
        // update if exists:
        if (null !== $limit) {
            Log::debug(sprintf('Existing budget limit is #%d, update this to amount %s', $limit->id, $amount));
            $limit->amount = $amount;
            $limit->save();

            return $limit;
        }
        Log::debug('No existing budget limit, create a new one');
        // or create one and return it.
        $limit = new BudgetLimit;
        $limit->budget()->associate($budget);
        $limit->start_date = $start->startOfDay();
        $limit->end_date   = $end->startOfDay();
        $limit->amount     = $amount;
        $limit->save();
        Log::debug(sprintf('Created new budget limit with ID #%d and amount %s', $limit->id, $amount));

        return $limit;
    }

    /**
     * @param string $oldName
     * @param string $newName
     */
    private function updateRuleActions(string $oldName, string $newName): void
    {
        $types   = ['set_budget',];
        $actions = RuleAction::leftJoin('rules', 'rules.id', '=', 'rule_actions.rule_id')
                             ->where('rules.user_id', $this->user->id)
                             ->whereIn('rule_actions.action_type', $types)
                             ->where('rule_actions.action_value', $oldName)
                             ->get(['rule_actions.*']);
        Log::debug(sprintf('Found %d actions to update.', $actions->count()));
        /** @var RuleAction $action */
        foreach ($actions as $action) {
            $action->action_value = $newName;
            $action->save();
            Log::debug(sprintf('Updated action %d: %s', $action->id, $action->action_value));
        }
    }

    /**
     * @param string $oldName
     * @param string $newName
     */
    private function updateRuleTriggers(string $oldName, string $newName): void
    {
        $types    = ['budget_is',];
        $triggers = RuleTrigger::leftJoin('rules', 'rules.id', '=', 'rule_triggers.rule_id')
                               ->where('rules.user_id', $this->user->id)
                               ->whereIn('rule_triggers.trigger_type', $types)
                               ->where('rule_triggers.trigger_value', $oldName)
                               ->get(['rule_triggers.*']);
        Log::debug(sprintf('Found %d triggers to update.', $triggers->count()));
        /** @var RuleTrigger $trigger */
        foreach ($triggers as $trigger) {
            $trigger->trigger_value = $newName;
            $trigger->save();
            Log::debug(sprintf('Updated trigger %d: %s', $trigger->id, $trigger->trigger_value));
        }
    }
}
