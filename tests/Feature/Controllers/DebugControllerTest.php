<?php
/**
 * DebugControllerTest.php
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

namespace Tests\Feature\Controllers;

use FireflyIII\Models\TransactionJournal;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use FireflyIII\Repositories\User\UserRepositoryInterface;
use Log;
use Mockery;
use Preferences;
use Tests\TestCase;

/**
 * Class HomeControllerTest
 *
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class DebugControllerTest extends TestCase
{
    /**
     *
     */
    public function setUp(): void
    {
        parent::setUp();
        Log::info(sprintf('Now in %s.', get_class($this)));
    }

    /**
     * @covers \FireflyIII\Http\Controllers\DebugController
     */
    public function testDisplayError(): void
    {
        $this->mockDefaultSession();
        // mock stuff
        $userRepos    = $this->mock(UserRepositoryInterface::class);
        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'demo'])->atLeast()->once()->andReturn(false);

        $this->be($this->user());
        $response = $this->get(route('error'));
        $response->assertStatus(500);
        $response->assertSee('A very simple test error');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\DebugController
     */
    public function testFlush(): void
    {
        $this->mockDefaultSession();
        // mock stuff
        $userRepos    = $this->mock(UserRepositoryInterface::class);
        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'demo'])->atLeast()->once()->andReturn(false);
        Preferences::shouldReceive('mark')->atLeast()->once();

        $this->be($this->user());
        $response = $this->get(route('flush'));
        $response->assertStatus(302);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\DebugController
     */
    public function testIndex(): void
    {
        $this->mockDefaultSession();
        $userRepos = $this->mock(UserRepositoryInterface::class);
        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'demo'])->atLeast()->once()->andReturn(false);

        $this->be($this->user());
        $response = $this->get(route('debug'));
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\DebugController
     */
    public function testRoutes(): void
    {
        $this->mockDefaultSession();
        $userRepos = $this->mock(UserRepositoryInterface::class);
        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'demo'])->atLeast()->once()->andReturn(false);

        $this->be($this->user());
        $response = $this->get(route('routes'));
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\DebugController
     */
    public function testTestFlash(): void
    {
        $this->mockDefaultSession();
        // mock stuff
        $userRepos    = $this->mock(UserRepositoryInterface::class);

        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'demo'])->atLeast()->once()->andReturn(false);

        $this->be($this->user());
        $response = $this->get(route('test-flash'));
        $response->assertStatus(302);
        $response->assertSessionHas('success');
        $response->assertSessionHas('info');
        $response->assertSessionHas('warning');
        $response->assertSessionHas('error');
    }

}
