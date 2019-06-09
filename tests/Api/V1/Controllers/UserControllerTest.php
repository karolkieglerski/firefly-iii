<?php
/**
 * UserControllerTest.php
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

namespace Tests\Api\V1\Controllers;


use FireflyIII\Repositories\User\UserRepositoryInterface;
use FireflyIII\Transformers\UserTransformer;
use FireflyIII\User;
use Illuminate\Support\Collection;
use Laravel\Passport\Passport;
use Log;
use Mockery;
use Tests\TestCase;

/**
 * Class UserControllerTest
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class UserControllerTest extends TestCase
{

    /**
     *
     */
    public function setUp(): void
    {
        parent::setUp();
        Passport::actingAs($this->user());
        Log::info(sprintf('Now in %s.', get_class($this)));

    }

    /**
     * Store new user.
     *
     * @covers \FireflyIII\Api\V1\Controllers\UserController
     * @covers \FireflyIII\Api\V1\Requests\UserRequest
     */
    public function testStoreBasic(): void
    {
        $data = [
            'email' => 'some_new@user' . random_int(1, 10000) . '.com',
        ];

        // mock
        $userRepos   = $this->mock(UserRepositoryInterface::class);
        $transformer = $this->mock(UserTransformer::class);
        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'owner'])->twice()->andReturn(true);
        $userRepos->shouldReceive('store')->once()->andReturn($this->user());

        // mock transformer
        $transformer->shouldReceive('setParameters')->withAnyArgs()->atLeast()->once();
        $transformer->shouldReceive('setCurrentScope')->withAnyArgs()->atLeast()->once()->andReturnSelf();
        $transformer->shouldReceive('getDefaultIncludes')->withAnyArgs()->atLeast()->once()->andReturn([]);
        $transformer->shouldReceive('getAvailableIncludes')->withAnyArgs()->atLeast()->once()->andReturn([]);
        $transformer->shouldReceive('transform')->atLeast()->once()->andReturn(['id' => 5]);

        // test API
        $response = $this->post(route('api.v1.users.store'), $data, ['Content-Type' => 'application/x-www-form-urlencoded']);
        $response->assertStatus(200);
    }

    /**
     * Store new user using JSON.
     *
     * @covers \FireflyIII\Api\V1\Controllers\UserController
     * @covers \FireflyIII\Api\V1\Requests\UserRequest
     */
    public function testStoreBasicJson(): void
    {
        $data = [
            'email'        => 'some_new@user' . random_int(1, 10000) . '.com',
            'blocked'      => true,
            'blocked_code' => 'email_changed',
        ];

        // mock
        $userRepos   = $this->mock(UserRepositoryInterface::class);
        $transformer = $this->mock(UserTransformer::class);
        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'owner'])->twice()->andReturn(true);
        $userRepos->shouldReceive('store')->once()->andReturn($this->user());

        // mock transformer
        $transformer->shouldReceive('setParameters')->withAnyArgs()->atLeast()->once();
        $transformer->shouldReceive('setCurrentScope')->withAnyArgs()->atLeast()->once()->andReturnSelf();
        $transformer->shouldReceive('getDefaultIncludes')->withAnyArgs()->atLeast()->once()->andReturn([]);
        $transformer->shouldReceive('getAvailableIncludes')->withAnyArgs()->atLeast()->once()->andReturn([]);
        $transformer->shouldReceive('transform')->atLeast()->once()->andReturn(['id' => 5]);

        // test API
        $response = $this->postJson('/api/v1/users', $data, ['Accept' => 'application/json']);
        $response->assertStatus(200);
    }

    /**
     * Store user with info already used.
     *
     * @covers \FireflyIII\Api\V1\Controllers\UserController
     * @covers \FireflyIII\Api\V1\Requests\UserRequest
     */
    public function testStoreNotUnique(): void
    {
        $data = [
            'email'   => $this->user()->email,
            'blocked' => 0,
        ];

        // mock
        $userRepos = $this->mock(UserRepositoryInterface::class);
        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'owner'])->twice()->andReturn(true);
        // test API
        $response = $this->post(route('api.v1.users.store'), $data, ['Accept' => 'application/json']);
        $response->assertStatus(422);
        $response->assertExactJson(
            [
                'message' => 'The given data was invalid.',
                'errors'  => [
                    'email' => [
                        'The email address has already been taken.',
                    ],
                ],
            ]
        );
    }

    /**
     * Store user with info already used.
     *
     * @covers \FireflyIII\Api\V1\Controllers\UserController
     * @covers \FireflyIII\Api\V1\Requests\UserRequest
     */
    public function testStoreNotUniqueJson(): void
    {
        $data = [
            'email'   => $this->user()->email,
            'blocked' => 0,
        ];

        // mock
        $userRepos = $this->mock(UserRepositoryInterface::class);
        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'owner'])->twice()->andReturn(true);
        // test API
        $response = $this->postJson('/api/v1/users', $data, ['Accept' => 'application/json']);
        $response->assertStatus(422);
        $response->assertExactJson(
            [
                'message' => 'The given data was invalid.',
                'errors'  => [
                    'email' => [
                        'The email address has already been taken.',
                    ],
                ],
            ]
        );
    }

    /**
     * Update user.
     *
     * @covers \FireflyIII\Api\V1\Controllers\UserController
     * @covers \FireflyIII\Api\V1\Requests\UserRequest
     */
    public function testUpdate(): void
    {
        // create a user first:
        $user = User::create(['email' => 'some@newu' . random_int(1, 10000) . 'ser.nl', 'password' => 'hello', 'blocked' => 0]);

        // data:
        $data = [
            'email'   => 'some-new@email' . random_int(1, 10000) . '.com',
            'blocked' => 0,
        ];

        // mock
        $userRepos   = $this->mock(UserRepositoryInterface::class);
        $transformer = $this->mock(UserTransformer::class);
        $userRepos->shouldReceive('update')->once()->andReturn($user);
        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'owner'])->twice()->andReturn(true);

        // mock transformer
        $transformer->shouldReceive('setParameters')->withAnyArgs()->atLeast()->once();
        $transformer->shouldReceive('setCurrentScope')->withAnyArgs()->atLeast()->once()->andReturnSelf();
        $transformer->shouldReceive('getDefaultIncludes')->withAnyArgs()->atLeast()->once()->andReturn([]);
        $transformer->shouldReceive('getAvailableIncludes')->withAnyArgs()->atLeast()->once()->andReturn([]);
        $transformer->shouldReceive('transform')->atLeast()->once()->andReturn(['id' => 5]);

        // call API
        $response = $this->put('/api/v1/users/' . $user->id, $data, ['Accept' => 'application/json']);
        $response->assertStatus(200);
    }

    /**
     * Update user.
     *
     * @covers \FireflyIII\Api\V1\Controllers\UserController
     * @covers \FireflyIII\Api\V1\Requests\UserRequest
     */
    public function testUpdateJson(): void
    {
        // create a user first:
        $user = User::create(['email' => 'some@newu' . random_int(1, 10000) . 'ser.nl', 'password' => 'hello', 'blocked' => 0]);

        // data:
        $data = [
            'email'   => 'some-new@email' . random_int(1, 10000) . '.com',
            'blocked' => 0,
        ];

        // mock
        $userRepos   = $this->mock(UserRepositoryInterface::class);
        $transformer = $this->mock(UserTransformer::class);
        $userRepos->shouldReceive('update')->once()->andReturn($user);
        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'owner'])->twice()->andReturn(true);

        // mock transformer
        $transformer->shouldReceive('setParameters')->withAnyArgs()->atLeast()->once();
        $transformer->shouldReceive('setCurrentScope')->withAnyArgs()->atLeast()->once()->andReturnSelf();
        $transformer->shouldReceive('getDefaultIncludes')->withAnyArgs()->atLeast()->once()->andReturn([]);
        $transformer->shouldReceive('getAvailableIncludes')->withAnyArgs()->atLeast()->once()->andReturn([]);
        $transformer->shouldReceive('transform')->atLeast()->once()->andReturn(['id' => 5]);

        // call API
        $response = $this->putJson('/api/v1/users/' . $user->id, $data, ['Accept' => 'application/json']);
        $response->assertStatus(200);
    }

}
