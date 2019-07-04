<?php
/**
 * AutoCompleteController.php
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

namespace FireflyIII\Http\Controllers\Json;

use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Models\Account;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use FireflyIII\Repositories\Category\CategoryRepositoryInterface;
use FireflyIII\Repositories\Currency\CurrencyRepositoryInterface;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use FireflyIII\Repositories\PiggyBank\PiggyBankRepositoryInterface;
use FireflyIII\Repositories\Tag\TagRepositoryInterface;
use FireflyIII\Repositories\TransactionType\TransactionTypeRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Log;

/**
 * Class AutoCompleteController.
 *
 * TODO autocomplete for transaction types.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class AutoCompleteController extends Controller
{

    /**
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function accounts(Request $request): JsonResponse
    {
        $accountTypes = explode(',', $request->get('types') ?? '');
        $search       = $request->get('search');
        /** @var AccountRepositoryInterface $repository */
        $repository = app(AccountRepositoryInterface::class);

        // filter the account types:
        $allowedAccountTypes  = [AccountType::ASSET, AccountType::EXPENSE, AccountType::REVENUE, AccountType::LOAN, AccountType::DEBT, AccountType::MORTGAGE,];
        $filteredAccountTypes = [];
        foreach ($accountTypes as $type) {
            if (in_array($type, $allowedAccountTypes, true)) {
                $filteredAccountTypes[] = $type;
            }
        }
        Log::debug('Now in accounts(). Filtering results.', $filteredAccountTypes);

        $return          = [];
        $result          = $repository->searchAccount((string)$search, $filteredAccountTypes);
        $defaultCurrency = app('amount')->getDefaultCurrency();

        /** @var Account $account */
        foreach ($result as $account) {
            $currency = $repository->getAccountCurrency($account);
            $currency = $currency ?? $defaultCurrency;
            $return[] = [
                'id'                      => $account->id,
                'name'                    => $account->name,
                'type'                    => $account->accountType->type,
                'currency_id'             => $currency->id,
                'currency_name'           => $currency->name,
                'currency_code'           => $currency->code,
                'currency_decimal_places' => $currency->decimal_places,
            ];
        }


        return response()->json($return);
    }

    /**
     * Searches in the titles of all transaction journals.
     * The result is limited to the top 15 unique results.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function allJournals(Request $request): JsonResponse
    {
        $search = (string)$request->get('search');
        /** @var JournalRepositoryInterface $repository */
        $repository = app(JournalRepositoryInterface::class);
        $result     = $repository->searchJournalDescriptions($search);

        // limit and unique
        $filtered = $result->unique('description');
        $limited  = $filtered->slice(0, 15);
        $array    = $limited->toArray();
        foreach ($array as $index => $item) {
            // give another key for consistency
            $array[$index]['name'] = $item['description'];
        }


        return response()->json($array);

    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function budgets(Request $request): JsonResponse
    {
        $search = (string)$request->get('search');
        /** @var BudgetRepositoryInterface $repository */
        $repository = app(BudgetRepositoryInterface::class);
        $result     = $repository->searchBudget($search);

        return response()->json($result->toArray());
    }

    /**
     * @param Request $request
     *
     * @return JsonResponse
     * @codeCoverageIgnore
     */
    public function categories(Request $request): JsonResponse
    {
        $query = (string)$request->get('search');
        /** @var CategoryRepositoryInterface $repository */
        $repository = app(CategoryRepositoryInterface::class);
        $result     = $repository->searchCategory($query);

        return response()->json($result->toArray());
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function currencyNames(Request $request): JsonResponse
    {
        $query = (string)$request->get('search');
        /** @var CurrencyRepositoryInterface $repository */
        $repository = app(CurrencyRepositoryInterface::class);
        $result     = $repository->searchCurrency($query)->toArray();
        foreach ($result as $index => $item) {
            $result[$index]['name'] = sprintf('%s (%s)', $item['name'], $item['code']);
        }

        return response()->json($result);
    }

    /**
     * @param Request $request
     *
     * @return JsonResponse
     * @codeCoverageIgnore
     */
    public function transactionTypes(Request $request): JsonResponse
    {
        $query = (string)$request->get('search');
        /** @var TransactionTypeRepositoryInterface $repository */
        $repository = app(TransactionTypeRepositoryInterface::class);
        $array      = $repository->searchTypes($query)->toArray();

        foreach ($array as $index => $item) {
            // different key for consistency.
            $array[$index]['name'] = $item['type'];
        }

        return response()->json($array);
    }

    /**
     * @return JsonResponse
     * @codeCoverageIgnore
     */
    public function currencies(): JsonResponse
    {
        /** @var CurrencyRepositoryInterface $repository */
        $repository = app(CurrencyRepositoryInterface::class);
        $return     = [];
        $collection = $repository->getAll();

        /** @var TransactionCurrency $currency */
        foreach ($collection as $currency) {
            $return[] = [
                'id'             => $currency->id,
                'name'           => $currency->name,
                'code'           => $currency->code,
                'symbol'         => $currency->symbol,
                'enabled'        => $currency->enabled,
                'decimal_places' => $currency->decimal_places,
            ];
        }

        return response()->json($return);
    }

    /**
     * @return JsonResponse
     * @codeCoverageIgnore
     */
    public function piggyBanks(): JsonResponse
    {
        /** @var PiggyBankRepositoryInterface $repository */
        $repository = app(PiggyBankRepositoryInterface::class);

        return response()->json($repository->getPiggyBanks()->toArray());
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @codeCoverageIgnore
     */
    public function tags(Request $request): JsonResponse
    {
        $search = (string)$request->get('search');
        /** @var TagRepositoryInterface $repository */
        $repository = app(TagRepositoryInterface::class);
        $result     = $repository->searchTags($search);
        $array      = $result->toArray();
        foreach ($array as $index => $item) {
            // rename field for consistency.
            $array[$index]['name'] = $item['tag'];
        }

        return response()->json($array);
    }

}
