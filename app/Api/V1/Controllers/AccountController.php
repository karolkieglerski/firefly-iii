<?php
/**
 * AccountController.php
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

namespace FireflyIII\Api\V1\Controllers;

use FireflyIII\Api\V1\Requests\AccountRequest;
use FireflyIII\Models\Account;
use FireflyIII\Models\AccountType;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Currency\CurrencyRepositoryInterface;
use FireflyIII\Transformers\AccountTransformer;
use FireflyIII\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use League\Fractal\Manager;
use League\Fractal\Pagination\IlluminatePaginatorAdapter;
use League\Fractal\Resource\Collection as FractalCollection;
use League\Fractal\Resource\Item;
use League\Fractal\Serializer\JsonApiSerializer;

/**
 * Class AccountController.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class AccountController extends Controller
{
    /** @var CurrencyRepositoryInterface The currency repository */
    private $currencyRepository;
    /** @var AccountRepositoryInterface The account repository */
    private $repository;

    /**
     * AccountController constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->middleware(
            function ($request, $next) {
                /** @var User $user */
                $user = auth()->user();
                // @var AccountRepositoryInterface repository
                $this->repository = app(AccountRepositoryInterface::class);
                $this->repository->setUser($user);

                $this->currencyRepository = app(CurrencyRepositoryInterface::class);
                $this->currencyRepository->setUser($user);

                return $next($request);
            }
        );
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \FireflyIII\Models\Account $account
     *
     * @return JsonResponse
     */
    public function delete(Account $account): JsonResponse
    {
        $this->repository->destroy($account, null);

        return response()->json([], 204);
    }

    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        // create some objects:
        $manager = new Manager;
        $baseUrl = $request->getSchemeAndHttpHost() . '/api/v1';

        // read type from URI
        $type = $request->get('type') ?? 'all';
        $this->parameters->set('type', $type);

        // types to get, page size:
        $types    = $this->mapTypes($this->parameters->get('type'));
        $pageSize = (int)app('preferences')->getForUser(auth()->user(), 'listPageSize', 50)->data;

        // get list of accounts. Count it and split it.
        $collection = $this->repository->getAccountsByType($types);
        $count      = $collection->count();
        $accounts   = $collection->slice(($this->parameters->get('page') - 1) * $pageSize, $pageSize);

        // make paginator:
        $paginator = new LengthAwarePaginator($accounts, $count, $pageSize, $this->parameters->get('page'));
        $paginator->setPath(route('api.v1.accounts.index') . $this->buildParams());

        // present to user.
        $manager->setSerializer(new JsonApiSerializer($baseUrl));
        $resource = new FractalCollection($accounts, new AccountTransformer($this->parameters), 'accounts');
        $resource->setPaginator(new IlluminatePaginatorAdapter($paginator));

        return response()->json($manager->createData($resource)->toArray())->header('Content-Type', 'application/vnd.api+json');
    }

    /**
     * Show single instance.
     *
     * @param Request $request
     * @param Account $account
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request, Account $account): JsonResponse
    {
        $manager = new Manager;

        // add include parameter:
        $include = $request->get('include') ?? '';
        $manager->parseIncludes($include);

        $baseUrl = $request->getSchemeAndHttpHost() . '/api/v1';
        $manager->setSerializer(new JsonApiSerializer($baseUrl));
        $resource = new Item($account, new AccountTransformer($this->parameters), 'accounts');

        return response()->json($manager->createData($resource)->toArray())->header('Content-Type', 'application/vnd.api+json');
    }

    /**
     * Store a new instance.
     *
     * @param AccountRequest $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(AccountRequest $request): JsonResponse
    {
        $data = $request->getAll();
        // if currency ID is 0, find the currency by the code:
        if (0 === $data['currency_id']) {
            $currency            = $this->currencyRepository->findByCodeNull($data['currency_code']);
            $data['currency_id'] = null === $currency ? 0 : $currency->id;
        }
        $account = $this->repository->store($data);
        $manager = new Manager;
        $baseUrl = $request->getSchemeAndHttpHost() . '/api/v1';
        $manager->setSerializer(new JsonApiSerializer($baseUrl));

        $resource = new Item($account, new AccountTransformer($this->parameters), 'accounts');

        return response()->json($manager->createData($resource)->toArray())->header('Content-Type', 'application/vnd.api+json');
    }

    /**
     * Update account.
     *
     * @param AccountRequest $request
     * @param Account        $account
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(AccountRequest $request, Account $account): JsonResponse
    {
        $data = $request->getAll();
        // if currency ID is 0, find the currency by the code:
        if (0 === $data['currency_id']) {
            $currency            = $this->currencyRepository->findByCodeNull($data['currency_code']);
            $data['currency_id'] = null === $currency ? 0 : $currency->id;
        }
        // set correct type:
        $data['type'] = config('firefly.shortNamesByFullName.' . $account->accountType->type);
        $this->repository->update($account, $data);
        $manager = new Manager;
        $baseUrl = $request->getSchemeAndHttpHost() . '/api/v1';
        $manager->setSerializer(new JsonApiSerializer($baseUrl));

        $resource = new Item($account, new AccountTransformer($this->parameters), 'accounts');

        return response()->json($manager->createData($resource)->toArray())->header('Content-Type', 'application/vnd.api+json');
    }

    /**
     * All the available types.
     *
     * @param string $type
     *
     * @return array
     */
    private function mapTypes(string $type): array
    {
        $types  = [
            'all'                        => [AccountType::DEFAULT, AccountType::CASH, AccountType::ASSET, AccountType::EXPENSE, AccountType::REVENUE,
                                             AccountType::INITIAL_BALANCE, AccountType::BENEFICIARY, AccountType::IMPORT, AccountType::RECONCILIATION,
                                             AccountType::LOAN,AccountType::DEBT, AccountType::MORTGAGE],
            'asset'                      => [AccountType::DEFAULT, AccountType::ASSET,],
            'cash'                       => [AccountType::CASH,],
            'expense'                    => [AccountType::EXPENSE, AccountType::BENEFICIARY,],
            'revenue'                    => [AccountType::REVENUE,],
            'special'                    => [AccountType::CASH, AccountType::INITIAL_BALANCE, AccountType::IMPORT, AccountType::RECONCILIATION,],
            'hidden'                     => [AccountType::INITIAL_BALANCE, AccountType::IMPORT, AccountType::RECONCILIATION],
            'liability'                  => [AccountType::DEBT, AccountType::LOAN, AccountType::MORTGAGE, AccountType::CREDITCARD],
            'liabilities'                => [AccountType::DEBT, AccountType::LOAN, AccountType::MORTGAGE, AccountType::CREDITCARD],
            'cc'                         => [AccountType::CREDITCARD],
            'creditcard'                 => [AccountType::CREDITCARD],
            'credit_card'                => [AccountType::CREDITCARD],
            AccountType::DEFAULT         => [AccountType::DEFAULT],
            AccountType::CASH            => [AccountType::CASH],
            AccountType::ASSET           => [AccountType::ASSET],
            AccountType::EXPENSE         => [AccountType::EXPENSE],
            AccountType::REVENUE         => [AccountType::REVENUE],
            AccountType::INITIAL_BALANCE => [AccountType::INITIAL_BALANCE],
            AccountType::BENEFICIARY     => [AccountType::BENEFICIARY],
            AccountType::IMPORT          => [AccountType::IMPORT],
            AccountType::RECONCILIATION  => [AccountType::RECONCILIATION],
            AccountType::LOAN            => [AccountType::LOAN],
            AccountType::MORTGAGE        => [AccountType::MORTGAGE],
            AccountType::DEBT            => [AccountType::DEBT],
            AccountType::CREDITCARD      => [AccountType::CREDITCARD],

        ];
        $return = $types['all'];
        if (isset($types[$type])) {
            $return = $types[$type];
        }

        return $return; // @codeCoverageIgnore
    }
}
