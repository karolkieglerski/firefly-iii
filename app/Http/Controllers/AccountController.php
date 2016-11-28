<?php
/**
 * AccountController.php
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
use ExpandedForm;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Helpers\Collector\JournalCollector;
use FireflyIII\Helpers\Collector\JournalCollectorInterface;
use FireflyIII\Http\Requests\AccountFormRequest;
use FireflyIII\Models\Account;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\Transaction;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Account\AccountRepositoryInterface as ARI;
use FireflyIII\Repositories\Account\AccountTaskerInterface;
use FireflyIII\Repositories\Currency\CurrencyRepositoryInterface;
use FireflyIII\Support\CacheProperties;
use Illuminate\Support\Collection;
use Input;
use Log;
use Navigation;
use Preferences;
use Session;
use Steam;
use URL;
use View;

/**
 * Class AccountController
 *
 * @package FireflyIII\Http\Controllers
 */
class AccountController extends Controller
{
    /**
     *
     */
    public function __construct()
    {
        parent::__construct();

        // translations:
        $this->middleware(
            function ($request, $next) {
                View::share('mainTitleIcon', 'fa-credit-card');
                View::share('title', trans('firefly.accounts'));

                return $next($request);
            }
        );
    }

    /**
     * @param string $what
     *
     * @return View
     */
    public function create(string $what = 'asset')
    {
        /** @var CurrencyRepositoryInterface $repository */
        $repository      = app(CurrencyRepositoryInterface::class);
        $currencies      = ExpandedForm::makeSelectList($repository->get());
        $defaultCurrency = Amount::getDefaultCurrency();
        $subTitleIcon    = config('firefly.subIconsByIdentifier.' . $what);
        $subTitle        = trans('firefly.make_new_' . $what . '_account');
        Session::flash(
            'preFilled',
            [
                'currency_id' => $defaultCurrency->id,
            ]
        );

        // put previous url in session if not redirect from store (not "create another").
        if (session('accounts.create.fromStore') !== true) {
            Session::put('accounts.create.url', URL::previous());
        }
        Session::forget('accounts.create.fromStore');
        Session::flash('gaEventCategory', 'accounts');
        Session::flash('gaEventAction', 'create-' . $what);

        return view('accounts.create', compact('subTitleIcon', 'what', 'subTitle', 'currencies'));

    }

    /**
     * @param ARI     $repository
     * @param Account $account
     *
     * @return View
     */
    public function delete(ARI $repository, Account $account)
    {
        $typeName    = config('firefly.shortNamesByFullName.' . $account->accountType->type);
        $subTitle    = trans('firefly.delete_' . $typeName . '_account', ['name' => $account->name]);
        $accountList = ExpandedForm::makeSelectListWithEmpty($repository->getAccountsByType([$account->accountType->type]));
        unset($accountList[$account->id]);

        // put previous url in session
        Session::put('accounts.delete.url', URL::previous());
        Session::flash('gaEventCategory', 'accounts');
        Session::flash('gaEventAction', 'delete-' . $typeName);

        return view('accounts.delete', compact('account', 'subTitle', 'accountList'));
    }

    /**
     * @param ARI     $repository
     * @param Account $account
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function destroy(ARI $repository, Account $account)
    {
        $type     = $account->accountType->type;
        $typeName = config('firefly.shortNamesByFullName.' . $type);
        $name     = $account->name;
        $moveTo   = $repository->find(intval(Input::get('move_account_before_delete')));

        $repository->destroy($account, $moveTo);

        Session::flash('success', strval(trans('firefly.' . $typeName . '_deleted', ['name' => $name])));
        Preferences::mark();

        return redirect(session('accounts.delete.url'));
    }

    /**
     * @param Account $account
     *
     * @return View
     */
    public function edit(Account $account)
    {

        $what         = config('firefly.shortNamesByFullName')[$account->accountType->type];
        $subTitle     = trans('firefly.edit_' . $what . '_account', ['name' => $account->name]);
        $subTitleIcon = config('firefly.subIconsByIdentifier.' . $what);
        /** @var CurrencyRepositoryInterface $repository */
        $repository = app(CurrencyRepositoryInterface::class);
        $currencies = ExpandedForm::makeSelectList($repository->get());

        // put previous url in session if not redirect from store (not "return_to_edit").
        if (session('accounts.edit.fromUpdate') !== true) {
            Session::put('accounts.edit.url', URL::previous());
        }
        Session::forget('accounts.edit.fromUpdate');

        // pre fill some useful values.

        // the opening balance is tricky:
        $openingBalanceAmount = $account->getOpeningBalanceAmount();
        $openingBalanceAmount = $account->getOpeningBalanceAmount() === '0' ? '' : $openingBalanceAmount;
        $openingBalanceDate   = $account->getOpeningBalanceDate();
        $openingBalanceDate   = $openingBalanceDate->year === 1900 ? null : $openingBalanceDate->format('Y-m-d');

        $preFilled = [
            'accountNumber'        => $account->getMeta('accountNumber'),
            'accountRole'          => $account->getMeta('accountRole'),
            'ccType'               => $account->getMeta('ccType'),
            'ccMonthlyPaymentDate' => $account->getMeta('ccMonthlyPaymentDate'),
            'BIC'                  => $account->getMeta('BIC'),
            'openingBalanceDate'   => $openingBalanceDate,
            'openingBalance'       => $openingBalanceAmount,
            'virtualBalance'       => $account->virtual_balance,
            'currency_id'          => $account->getMeta('currency_id'),
        ];
        Session::flash('preFilled', $preFilled);
        Session::flash('gaEventCategory', 'accounts');
        Session::flash('gaEventAction', 'edit-' . $what);

        return view('accounts.edit', compact('currencies', 'account', 'subTitle', 'subTitleIcon', 'openingBalance', 'what'));
    }

    /**
     * @param ARI    $repository
     * @param string $what
     *
     * @return View
     */
    public function index(ARI $repository, string $what)
    {
        $what = $what ?? 'asset';

        $subTitle     = trans('firefly.' . $what . '_accounts');
        $subTitleIcon = config('firefly.subIconsByIdentifier.' . $what);
        $types        = config('firefly.accountTypesByIdentifier.' . $what);
        $accounts     = $repository->getAccountsByType($types);
        /** @var Carbon $start */
        $start = clone session('start', Carbon::now()->startOfMonth());
        /** @var Carbon $end */
        $end = clone session('end', Carbon::now()->endOfMonth());
        $start->subDay();

        $ids           = $accounts->pluck('id')->toArray();
        $startBalances = Steam::balancesById($ids, $start);
        $endBalances   = Steam::balancesById($ids, $end);
        $activities    = Steam::getLastActivities($ids);

        $accounts->each(
            function (Account $account) use ($activities, $startBalances, $endBalances) {
                $account->lastActivityDate = $this->isInArray($activities, $account->id);
                $account->startBalance     = $this->isInArray($startBalances, $account->id);
                $account->endBalance       = $this->isInArray($endBalances, $account->id);
            }
        );

        return view('accounts.index', compact('what', 'subTitleIcon', 'subTitle', 'accounts'));
    }

    /**
     * @param JournalCollectorInterface $collector
     * @param Account                   $account
     *
     * @return View
     */
    public function show(JournalCollectorInterface $collector, Account $account)
    {
        if ($account->accountType->type === AccountType::INITIAL_BALANCE) {
            return $this->redirectToOriginalAccount($account);
        }
        // show journals from current period only:
        $subTitleIcon = config('firefly.subIconsByIdentifier.' . $account->accountType->type);
        $subTitle     = $account->name;
        $range        = Preferences::get('viewRange', '1M')->data;
        $start        = session('start', Navigation::startOfPeriod(new Carbon, $range));
        $end          = session('end', Navigation::endOfPeriod(new Carbon, $range));
        $page         = intval(Input::get('page')) === 0 ? 1 : intval(Input::get('page'));
        $pageSize     = intval(Preferences::get('transactionPageSize', 50)->data);

        // grab those journals:
        $collector->setAccounts(new Collection([$account]))->setRange($start, $end)->setLimit($pageSize)->setPage($page);
        $journals = $collector->getPaginatedJournals();
        $journals->setPath('accounts/show/' . $account->id);

        // generate entries for each period (and cache those)
        $entries = $this->periodEntries($account);

        return view('accounts.show', compact('account', 'what', 'entries', 'subTitleIcon', 'journals', 'subTitle', 'start', 'end'));
    }

    /**
     * @param ARI     $repository
     * @param Account $account
     *
     * @return View
     */
    public function showAll(AccountRepositoryInterface $repository, Account $account)
    {
        $subTitle = sprintf('%s (%s)', $account->name, strtolower(trans('firefly.everything')));
        $page     = intval(Input::get('page')) === 0 ? 1 : intval(Input::get('page'));
        $pageSize = intval(Preferences::get('transactionPageSize', 50)->data);

        // replace with journal collector:
        $collector = new JournalCollector(auth()->user());
        $collector->setAccounts(new Collection([$account]))->setLimit($pageSize)->setPage($page);
        $journals = $collector->getPaginatedJournals();
        $journals->setPath('accounts/show/' . $account->id . '/all');

        // get oldest and newest journal for account:
        $start = $repository->oldestJournalDate($account);
        $end   = $repository->newestJournalDate($account);

        return view('accounts.show_with_date', compact('account', 'journals', 'subTitle', 'start', 'end'));
    }

    /**
     * @param Account $account
     * @param string  $date
     *
     * @return View
     */
    public function showWithDate(Account $account, string $date)
    {
        $carbon   = new Carbon($date);
        $range    = Preferences::get('viewRange', '1M')->data;
        $start    = Navigation::startOfPeriod($carbon, $range);
        $end      = Navigation::endOfPeriod($carbon, $range);
        $subTitle = $account->name . ' (' . Navigation::periodShow($start, $range) . ')';
        $page     = intval(Input::get('page')) === 0 ? 1 : intval(Input::get('page'));
        $pageSize = intval(Preferences::get('transactionPageSize', 50)->data);

        // replace with journal collector:
        $collector = new JournalCollector(auth()->user());
        $collector->setAccounts(new Collection([$account]))->setRange($start, $end)->setLimit($pageSize)->setPage($page);
        $journals = $collector->getPaginatedJournals();
        $journals->setPath('accounts/show/' . $account->id . '/' . $date);

        return view('accounts.show_with_date', compact('category', 'date', 'account', 'journals', 'subTitle', 'carbon', 'start', 'end'));
    }

    /**
     * @param AccountFormRequest $request
     * @param ARI                $repository
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     *
     */
    public function store(AccountFormRequest $request, ARI $repository)
    {
        $data    = $request->getAccountData();
        $account = $repository->store($data);

        Session::flash('success', strval(trans('firefly.stored_new_account', ['name' => $account->name])));
        Preferences::mark();

        // update preferences if necessary:
        $frontPage = Preferences::get('frontPageAccounts', [])->data;
        if (count($frontPage) > 0) {
            $frontPage[] = $account->id;
            Preferences::set('frontPageAccounts', $frontPage);
        }

        if (intval(Input::get('create_another')) === 1) {
            // set value so create routine will not overwrite URL:
            Session::put('accounts.create.fromStore', true);

            return redirect(route('accounts.create', [$request->input('what')]))->withInput();
        }

        // redirect to previous URL.
        return redirect(session('accounts.create.url'));
    }

    /**
     * @param AccountFormRequest $request
     * @param ARI                $repository
     * @param Account            $account
     *
     * @return $this|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function update(AccountFormRequest $request, ARI $repository, Account $account)
    {
        $data = $request->getAccountData();
        $repository->update($account, $data);

        Session::flash('success', strval(trans('firefly.updated_account', ['name' => $account->name])));
        Preferences::mark();

        if (intval(Input::get('return_to_edit')) === 1) {
            // set value so edit routine will not overwrite URL:
            Session::put('accounts.edit.fromUpdate', true);

            return redirect(route('accounts.edit', [$account->id]))->withInput(['return_to_edit' => 1]);
        }

        // redirect to previous URL.
        return redirect(session('accounts.edit.url'));

    }


    /**
     * @param array $array
     * @param int   $entryId
     *
     * @return null|mixed
     */
    protected function isInArray(array $array, int $entryId)
    {
        if (isset($array[$entryId])) {
            return $array[$entryId];
        }

        return '';
    }

    /**
     * This method returns "period entries", so nov-2015, dec-2015, etc etc (this depends on the users session range)
     * and for each period, the amount of money spent and earned. This is a complex operation which is cached for
     * performance reasons.
     *
     * @param Account $account The account involved.
     *
     * @return Collection
     */
    private function periodEntries(Account $account): Collection
    {
        /** @var ARI $repository */
        $repository = app(ARI::class);
        /** @var AccountTaskerInterface $tasker */
        $tasker = app(AccountTaskerInterface::class);

        $start   = $repository->oldestJournalDate($account);
        $range   = Preferences::get('viewRange', '1M')->data;
        $start   = Navigation::startOfPeriod($start, $range);
        $end     = Navigation::endOfX(new Carbon, $range);
        $entries = new Collection;

        // properties for cache
        $cache = new CacheProperties;
        $cache->addProperty($start);
        $cache->addProperty($end);
        $cache->addProperty('account-show-period-entries');
        $cache->addProperty($account->id);

        if ($cache->has()) {
            Log::debug('Entries are cached, return cache.');

            return $cache->get();
        }

        // only include asset accounts when this account is an asset:
        $assets = new Collection;
        if (in_array($account->accountType->type, [AccountType::ASSET, AccountType::DEFAULT])) {
            $assets = $repository->getAccountsByType([AccountType::ASSET, AccountType::DEFAULT]);
        }
        Log::debug('Going to get period expenses and incomes.');
        while ($end >= $start) {
            $end        = Navigation::startOfPeriod($end, $range);
            $currentEnd = Navigation::endOfPeriod($end, $range);
            $spent      = $tasker->amountOutInPeriod(new Collection([$account]), $assets, $end, $currentEnd);
            $earned     = $tasker->amountInInPeriod(new Collection([$account]), $assets, $end, $currentEnd);
            $dateStr    = $end->format('Y-m-d');
            $dateName   = Navigation::periodShow($end, $range);
            $entries->push([$dateStr, $dateName, $spent, $earned]);
            $end = Navigation::subtractPeriod($end, $range, 1);

        }
        $cache->store($entries);

        return $entries;
    }

    /**
     * @param Account $account
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     * @throws FireflyException
     */
    private function redirectToOriginalAccount(Account $account)
    {
        /** @var Transaction $transaction */
        $transaction = $account->transactions()->first();
        if (is_null($transaction)) {
            throw new FireflyException('Expected a transaction. This account has none. BEEP, error.');
        }

        $journal = $transaction->transactionJournal;
        /** @var Transaction $opposingTransaction */
        $opposingTransaction = $journal->transactions()->where('transactions.id', '!=', $transaction->id)->first();

        if (is_null($opposingTransaction)) {
            throw new FireflyException('Expected an opposing transaction. This account has none. BEEP, error.');
        }

        return redirect(route('accounts.show', [$opposingTransaction->account_id]));
    }
}
