<?php

/**
 * UserController.php
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

use FireflyIII\Api\V1\Requests\UserStoreRequest;
use FireflyIII\Api\V1\Requests\UserUpdateRequest;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Repositories\User\UserRepositoryInterface;
use FireflyIII\Transformers\UserTransformer;
use FireflyIII\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use League\Fractal\Pagination\IlluminatePaginatorAdapter;
use League\Fractal\Resource\Collection as FractalCollection;
use League\Fractal\Resource\Item;


/**
 * Class UserController.
 *
 */
class UserController extends Controller
{

    /** @var UserRepositoryInterface The user repository */
    private $repository;

    /**
     * UserController constructor.
     *
     * @codeCoverageIgnore
     */
    public function __construct()
    {
        parent::__construct();
        $this->middleware(
            function ($request, $next) {
                /** @var UserRepositoryInterface repository */
                $this->repository = app(UserRepositoryInterface::class);

                return $next($request);
            }
        );
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param User $user
     *
     * @return JsonResponse
     * @throws FireflyException
     * @codeCoverageIgnore
     */
    public function delete(User $user): JsonResponse
    {
        /** @var User $admin */
        $admin = auth()->user();
        if ($admin->id !== $user->id && $this->repository->hasRole($admin, 'owner')) {
            $this->repository->destroy($user);

            return response()->json([], 204);
        }
        throw new FireflyException('No access to method.'); // @codeCoverageIgnore
    }

    /**
     * Display a listing of the resource.
     *
     * @return JsonResponse
     * @codeCoverageIgnore
     */
    public function index(): JsonResponse
    {
        // user preferences
        $pageSize = (int)app('preferences')->getForUser(auth()->user(), 'listPageSize', 50)->data;
        $manager  = $this->getManager();

        // build collection
        $collection = $this->repository->all();
        $count      = $collection->count();
        $users      = $collection->slice(($this->parameters->get('page') - 1) * $pageSize, $pageSize);

        // make paginator:
        $paginator = new LengthAwarePaginator($users, $count, $pageSize, $this->parameters->get('page'));
        $paginator->setPath(route('api.v1.users.index') . $this->buildParams());

        // make resource
        /** @var UserTransformer $transformer */
        $transformer = app(UserTransformer::class);
        $transformer->setParameters($this->parameters);

        $resource = new FractalCollection($users, $transformer, 'users');
        $resource->setPaginator(new IlluminatePaginatorAdapter($paginator));

        return response()->json($manager->createData($resource)->toArray())->header('Content-Type', 'application/vnd.api+json');
    }

    /**
     * Show a single user.
     *
     * @param User $user
     *
     * @return JsonResponse
     * @codeCoverageIgnore
     */
    public function show(User $user): JsonResponse
    {
        // make manager
        $manager = $this->getManager();
        // make resource
        /** @var UserTransformer $transformer */
        $transformer = app(UserTransformer::class);
        $transformer->setParameters($this->parameters);

        $resource = new Item($user, $transformer, 'users');

        return response()->json($manager->createData($resource)->toArray())->header('Content-Type', 'application/vnd.api+json');
    }

    /**
     * Store a new user.
     *
     * @param UserStoreRequest $request
     *
     * @return JsonResponse
     */
    public function store(UserStoreRequest $request): JsonResponse
    {
        $data    = $request->getAll();
        $user    = $this->repository->store($data);
        $manager = $this->getManager();

        // make resource

        /** @var UserTransformer $transformer */
        $transformer = app(UserTransformer::class);
        $transformer->setParameters($this->parameters);

        $resource = new Item($user, $transformer, 'users');

        return response()->json($manager->createData($resource)->toArray())->header('Content-Type', 'application/vnd.api+json');
    }

    /**
     * Update a user.
     *
     * @param UserUpdateRequest $request
     * @param User              $user
     *
     * @return JsonResponse
     */
    public function update(UserUpdateRequest $request, User $user): JsonResponse
    {
        $data    = $request->getAll();
        $user    = $this->repository->update($user, $data);
        $manager = $this->getManager();
        // make resource
        /** @var UserTransformer $transformer */
        $transformer = app(UserTransformer::class);
        $transformer->setParameters($this->parameters);

        $resource = new Item($user, $transformer, 'users');

        return response()->json($manager->createData($resource)->toArray())->header('Content-Type', 'application/vnd.api+json');

    }

}
