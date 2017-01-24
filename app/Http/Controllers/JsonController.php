<?php
/**
 * JsonController.php
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
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Helpers\Collector\JournalCollectorInterface;
use FireflyIII\Models\AccountType;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Account\AccountTaskerInterface;
use FireflyIII\Repositories\Bill\BillRepositoryInterface;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use FireflyIII\Repositories\Category\CategoryRepositoryInterface;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use FireflyIII\Repositories\Tag\TagRepositoryInterface;
use FireflyIII\Support\CacheProperties;
use Illuminate\Http\Request;
use Preferences;
use Response;

/**
 * Class JsonController
 *
 * @package FireflyIII\Http\Controllers
 */
class JsonController extends Controller
{
    /**
     * JsonController constructor.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @param Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function action(Request $request)
    {
        $count   = intval($request->get('count')) > 0 ? intval($request->get('count')) : 1;
        $keys    = array_keys(config('firefly.rule-actions'));
        $actions = [];
        foreach ($keys as $key) {
            $actions[$key] = trans('firefly.rule_action_' . $key . '_choice');
        }
        $view = view('rules.partials.action', compact('actions', 'count'))->render();


        return Response::json(['html' => $view]);
    }

    /**
     * Returns a JSON list of all accounts.
     *
     * @param AccountRepositoryInterface $repository
     *
     * @return \Illuminate\Http\JsonResponse
     *
     */
    public function allAccounts(AccountRepositoryInterface $repository)
    {
        $return = array_unique(
            $repository->getAccountsByType(
                [AccountType::REVENUE, AccountType::EXPENSE, AccountType::BENEFICIARY, AccountType::DEFAULT, AccountType::ASSET]
            )->pluck('name')->toArray()
        );
        sort($return);

        return Response::json($return);

    }

    /**
     * @param JournalCollectorInterface $collector
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function allTransactionJournals(JournalCollectorInterface $collector)
    {
        $collector->setLimit(100)->setPage(1);
        $return = array_unique($collector->getJournals()->pluck('description')->toArray());
        sort($return);

        return Response::json($return);


    }

    /**
     * @param BillRepositoryInterface $repository
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function boxBillsPaid(BillRepositoryInterface $repository)
    {
        $start = session('start', Carbon::now()->startOfMonth());
        $end   = session('end', Carbon::now()->endOfMonth());

        /*
         * Since both this method and the chart use the exact same data, we can suffice
         * with calling the one method in the bill repository that will get this amount.
         */
        $amount = $repository->getBillsPaidInRange($start, $end); // will be a negative amount.
        $amount = bcmul($amount, '-1');

        $data = ['box' => 'bills-paid', 'amount' => Amount::format($amount, false), 'amount_raw' => $amount];

        return Response::json($data);
    }

    /**
     * @param BillRepositoryInterface $repository
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function boxBillsUnpaid(BillRepositoryInterface $repository)
    {
        $start  = session('start', Carbon::now()->startOfMonth());
        $end    = session('end', Carbon::now()->endOfMonth());
        $amount = $repository->getBillsUnpaidInRange($start, $end); // will be a positive amount.
        $data   = ['box' => 'bills-unpaid', 'amount' => Amount::format($amount, false), 'amount_raw' => $amount];

        return Response::json($data);
    }

    /**
     * @param AccountTaskerInterface     $accountTasker
     * @param AccountRepositoryInterface $repository
     *
     * @return \Illuminate\Http\JsonResponse
     *
     */
    public function boxIn(AccountTaskerInterface $accountTasker, AccountRepositoryInterface $repository)
    {
        $start = session('start', Carbon::now()->startOfMonth());
        $end   = session('end', Carbon::now()->endOfMonth());

        // works for json too!
        $cache = new CacheProperties;
        $cache->addProperty($start);
        $cache->addProperty($end);
        $cache->addProperty('box-in');
        if ($cache->has()) {
            return Response::json($cache->get());
        }
        $accounts = $repository->getAccountsByType([AccountType::DEFAULT, AccountType::ASSET, AccountType::CASH]);
        $assets   = $repository->getAccountsByType([AccountType::DEFAULT, AccountType::ASSET]);
        $amount   = $accountTasker->amountInInPeriod($accounts, $assets, $start, $end);
        $data     = ['box' => 'in', 'amount' => Amount::format($amount, false), 'amount_raw' => $amount];
        $cache->store($data);

        return Response::json($data);
    }

    /**
     * @param AccountTaskerInterface     $accountTasker
     * @param AccountRepositoryInterface $repository
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function boxOut(AccountTaskerInterface $accountTasker, AccountRepositoryInterface $repository)
    {
        $start = session('start', Carbon::now()->startOfMonth());
        $end   = session('end', Carbon::now()->endOfMonth());

        // works for json too!
        $cache = new CacheProperties;
        $cache->addProperty($start);
        $cache->addProperty($end);
        $cache->addProperty('box-out');
        if ($cache->has()) {
            return Response::json($cache->get());
        }

        $accounts = $repository->getAccountsByType([AccountType::DEFAULT, AccountType::ASSET, AccountType::CASH]);
        $assets   = $repository->getAccountsByType([AccountType::DEFAULT, AccountType::ASSET]);
        $amount   = $accountTasker->amountOutInPeriod($accounts, $assets, $start, $end);

        $data = ['box' => 'out', 'amount' => Amount::format($amount, false), 'amount_raw' => $amount];
        $cache->store($data);

        return Response::json($data);
    }

    /**
     * @param BudgetRepositoryInterface $repository
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function budgets(BudgetRepositoryInterface $repository)
    {
        $return = array_unique($repository->getBudgets()->pluck('name')->toArray());
        sort($return);

        return Response::json($return);
    }

    /**
     * Returns a list of categories.
     *
     * @param CategoryRepositoryInterface $repository
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function categories(CategoryRepositoryInterface $repository)
    {
        $return = array_unique($repository->getCategories()->pluck('name')->toArray());
        sort($return);

        return Response::json($return);
    }

    /**
     * @return \Illuminate\Http\JsonResponse
     */
    public function endTour()
    {
        Preferences::set('tour', false);

        return Response::json('true');
    }

    /**
     * Returns a JSON list of all beneficiaries.
     *
     * @param AccountRepositoryInterface $repository
     *
     * @return \Illuminate\Http\JsonResponse
     *
     */
    public function expenseAccounts(AccountRepositoryInterface $repository)
    {
        $return = array_unique($repository->getAccountsByType([AccountType::EXPENSE, AccountType::BENEFICIARY])->pluck('name')->toArray());
        sort($return);

        return Response::json($return);
    }

    /**
     * @param AccountRepositoryInterface $repository
     *
     * @return \Illuminate\Http\JsonResponse
     *
     */
    public function revenueAccounts(AccountRepositoryInterface $repository)
    {
        $return = array_unique($repository->getAccountsByType([AccountType::REVENUE])->pluck('name')->toArray());
        sort($return);

        return Response::json($return);
    }

    /**
     * Returns a JSON list of all beneficiaries.
     *
     * @param TagRepositoryInterface $tagRepository
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function tags(TagRepositoryInterface $tagRepository)
    {
        $return = array_unique($tagRepository->get()->pluck('tag')->toArray());
        sort($return);

        return Response::json($return);

    }

    /**
     *
     */
    public function tour()
    {
        $pref = Preferences::get('tour', true);
        if (!$pref) {
            throw new FireflyException('Cannot find preference for tour. Exit.');
        }
        $headers = ['main-content', 'sidebar-toggle', 'account-menu', 'budget-menu', 'report-menu', 'transaction-menu', 'option-menu', 'main-content-end'];
        $steps   = [];
        foreach ($headers as $header) {
            $steps[] = [
                'element' => '#' . $header,
                'title'   => trans('help.' . $header . '-title'),
                'content' => trans('help.' . $header . '-text'),
            ];
        }
        $steps[0]['orphan']    = true;// orphan and backdrop for first element.
        $steps[0]['backdrop']  = true;
        $steps[1]['placement'] = 'left';// sidebar position left:
        $steps[7]['orphan']    = true; // final in the center again.
        $steps[7]['backdrop']  = true;
        $template              = view('json.tour')->render();

        return Response::json(['steps' => $steps, 'template' => $template]);
    }

    /**
     * @param JournalCollectorInterface $collector
     * @param string                    $what
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function transactionJournals(JournalCollectorInterface $collector, string $what)
    {
        $type  = config('firefly.transactionTypesByWhat.' . $what);
        $types = [$type];

        $collector->setTypes($types)->setLimit(100)->setPage(1);
        $return = array_unique($collector->getJournals()->pluck('description')->toArray());
        sort($return);

        return Response::json($return);


    }

    /**
     *
     */
    public function transactionTypes(JournalRepositoryInterface $repository)
    {
        $return = array_unique($repository->getTransactionTypes()->pluck('type')->toArray());
        sort($return);

        return Response::json($return);
    }

    /**
     * @param Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function trigger(Request $request)
    {
        $count    = intval($request->get('count')) > 0 ? intval($request->get('count')) : 1;
        $keys     = array_keys(config('firefly.rule-triggers'));
        $triggers = [];
        foreach ($keys as $key) {
            if ($key != 'user_action') {
                $triggers[$key] = trans('firefly.rule_trigger_' . $key . '_choice');
            }
        }

        $view = view('rules.partials.trigger', compact('triggers', 'count'))->render();


        return Response::json(['html' => $view]);
    }

}
