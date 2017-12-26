<?php
/**
 * IsDemoUserTest.php
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

namespace Tests\Unit\Helpers;

use FireflyIII\Http\Middleware\IsDemoUser;
use Route;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

/**
 * Class IsDemoUserTest
 */
class IsDemoUserTest extends TestCase
{
    /**
     * @covers \FireflyIII\Http\Middleware\IsDemoUser::handle
     */
    public function testMiddlewareNotAuthenticated()
    {
        $this->withoutExceptionHandling();
        $response = $this->get('/_test/is-demo');
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
    }

    /**
     * @covers \FireflyIII\Http\Middleware\IsDemoUser::handle
     */
    public function testMiddlewareAuthenticated()
    {
        $this->withoutExceptionHandling();
        $this->be($this->user());
        $response = $this->get('/_test/is-demo');
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
    }

    /**
     * @covers \FireflyIII\Http\Middleware\IsDemoUser::handle
     */
    public function testMiddlewareDemoUser()
    {
        $this->withoutExceptionHandling();
        $this->be($this->demoUser());
        $response = $this->get('/_test/is-demo');

        $this->assertEquals(Response::HTTP_FOUND, $response->getStatusCode());
        $response->assertSessionHas('warning', strval(trans('firefly.not_available_demo_user')));
        $response->assertRedirect(route('index'));
    }

    /**
     * Set up test
     */
    protected function setUp()
    {
        parent::setUp();

        Route::middleware(IsDemoUser::class)->any(
            '/_test/is-demo', function () {
            return 'OK';
        }
        );
    }
}