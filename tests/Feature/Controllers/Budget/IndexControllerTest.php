<?php
/**
 * IndexControllerTest.php
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

namespace Tests\Feature\Controllers\Budget;

use Amount;
use Carbon\Carbon;
use Exception;
use FireflyIII\Helpers\Fiscal\FiscalHelperInterface;
use FireflyIII\Models\Budget;
use FireflyIII\Models\Preference;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use FireflyIII\Repositories\User\UserRepositoryInterface;
use Illuminate\Support\Collection;
use Log;
use Mockery;
use Preferences;
use Tests\TestCase;

/**
 *
 * Class IndexControllerTest
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class IndexControllerTest extends TestCase
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
     * @covers       \FireflyIII\Http\Controllers\Budget\IndexController
     * @dataProvider dateRangeProvider
     *
     * @param string $range
     */
    public function testIndex(string $range): void
    {
        // mock stuff
        $budget = $this->getRandomBudget();
        $budgetLimit             = $this->getRandomBudgetLimit();
        $budgetLimit->start_date = Carbon::now()->startOfMonth();
        $budgetLimit->end_date   = Carbon::now()->endOfMonth();
        $budgetInfo              = [
            $budget->id => [
                'spent'      => '0',
                'budgeted'   => '0',
                'currentRep' => false,
            ],
        ];

        $accountRepos = $this->mock(AccountRepositoryInterface::class);
        $repository   = $this->mock(BudgetRepositoryInterface::class);
        $userRepos    = $this->mock(UserRepositoryInterface::class);

        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'owner'])->andReturn(true)->atLeast()->once();
        $accountRepos->shouldReceive('getAccountsByType')->andReturn(new Collection);

        $repository->shouldReceive('cleanupBudgets');
        $repository->shouldReceive('getActiveBudgets')->andReturn(new Collection([$budget]));
        $repository->shouldReceive('getInactiveBudgets')->andReturn(new Collection);
        $repository->shouldReceive('getAvailableBudget')->andReturn('100.123');
        $repository->shouldReceive('spentInPeriod')->andReturn('-1');
        $repository->shouldReceive('collectBudgetInformation')->andReturn($budgetInfo);
        $repository->shouldReceive('getBudgetLimits')->andReturn(new Collection([$budgetLimit]));

        $this->mockDefaultSession();
        $this->mockIntroPreference('shown_demo_budgets_index');
        // list size
        $pref       = new Preference;
        $pref->data = 50;
        Preferences::shouldReceive('get')->withArgs(['listPageSize', 50])->atLeast()->once()->andReturn($pref);
        Amount::shouldReceive('formatAnything')->andReturn('123');

        $this->be($this->user());
        $this->changeDateRange($this->user(), $range);
        $response                = $this->get(route('budgets.index'));
        $response->assertStatus(200);
        // has bread crumb
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers       \FireflyIII\Http\Controllers\Budget\IndexController
     * @dataProvider dateRangeProvider
     *
     * @param string $range
     * @throws Exception
     */
    public function testIndexOutOfRange(string $range): void
    {
        $budget      = $this->getRandomBudget();
        $budgetLimit = $this->getRandomBudgetLimit();
        $budgetInfo  = [
            $budget->id => [
                'spent'      => '0',
                'budgeted'   => '0',
                'currentRep' => false,
            ],
        ];

        // set budget limit to current month:
        $budgetLimit->start_date = Carbon::now()->startOfMonth();
        $budgetLimit->end_date   = Carbon::now()->endOfMonth();

        $accountRepos = $this->mock(AccountRepositoryInterface::class);
        $repository   = $this->mock(BudgetRepositoryInterface::class);
        $userRepos    = $this->mock(UserRepositoryInterface::class);
        $fiscalHelper = $this->mock(FiscalHelperInterface::class);
        $date         = new Carbon;
        $fiscalHelper->shouldReceive('endOfFiscalYear')->atLeast()->once()->andReturn($date);
        $fiscalHelper->shouldReceive('startOfFiscalYear')->atLeast()->once()->andReturn($date);

        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'owner'])->andReturn(true)->atLeast()->once();
        $accountRepos->shouldReceive('getAccountsByType')->andReturn(new Collection);

        $repository->shouldReceive('cleanupBudgets');
        $repository->shouldReceive('getActiveBudgets')->andReturn(new Collection([$budget]));
        $repository->shouldReceive('getInactiveBudgets')->andReturn(new Collection);
        $repository->shouldReceive('getAvailableBudget')->andReturn('100.123');
        $repository->shouldReceive('spentInPeriod')->andReturn('-1');
        $repository->shouldReceive('getBudgetLimits')->andReturn(new Collection([$budgetLimit]));
        $repository->shouldReceive('collectBudgetInformation')->andReturn($budgetInfo);

        $this->mockDefaultSession();
        $this->mockIntroPreference('shown_demo_budgets_index');
        // list size
        $pref       = new Preference;
        $pref->data = 50;
        Preferences::shouldReceive('get')->withArgs(['listPageSize', 50])->atLeast()->once()->andReturn($pref);
        Amount::shouldReceive('formatAnything')->andReturn('123');

        $this->be($this->user());
        $today = new Carbon;
        $today->startOfMonth();
        $this->changeDateRange($this->user(), $range);
        $response = $this->get(route('budgets.index', [$today->format('Y-m-d')]));
        $response->assertStatus(200);
        // has bread crumb
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers       \FireflyIII\Http\Controllers\Budget\IndexController
     * @dataProvider dateRangeProvider
     *
     * @param string $range
     * @throws Exception
     */
    public function testIndexWithDate(string $range): void
    {
        $budget      = $this->getRandomBudget();
        $budgetLimit = $this->getRandomBudgetLimit();
        $budgetInfo  = [
            $budget->id => [
                'spent'      => '0',
                'budgeted'   => '0',
                'currentRep' => false,
            ],
        ];

        // set budget limit to current month:
        $budgetLimit->start_date = Carbon::now()->startOfMonth();
        $budgetLimit->end_date   = Carbon::now()->endOfMonth();

        $accountRepos = $this->mock(AccountRepositoryInterface::class);
        $repository   = $this->mock(BudgetRepositoryInterface::class);
        $userRepos    = $this->mock(UserRepositoryInterface::class);
        $fiscalHelper = $this->mock(FiscalHelperInterface::class);
        $date         = new Carbon;
        $fiscalHelper->shouldReceive('endOfFiscalYear')->atLeast()->once()->andReturn($date);
        $fiscalHelper->shouldReceive('startOfFiscalYear')->atLeast()->once()->andReturn($date);
        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'owner'])->andReturn(true)->atLeast()->once();
        $accountRepos->shouldReceive('getAccountsByType')->andReturn(new Collection);
        $repository->shouldReceive('cleanupBudgets');
        $repository->shouldReceive('getActiveBudgets')->andReturn(new Collection([$budget]));
        $repository->shouldReceive('getInactiveBudgets')->andReturn(new Collection);
        $repository->shouldReceive('getAvailableBudget')->andReturn('100.123');
        $repository->shouldReceive('spentInPeriod')->andReturn('-1');
        $repository->shouldReceive('getBudgetLimits')->andReturn(new Collection([$budgetLimit]));
        $repository->shouldReceive('collectBudgetInformation')->andReturn($budgetInfo);

        $this->mockDefaultSession();
        $this->mockIntroPreference('shown_demo_budgets_index');
        // list size
        $pref       = new Preference;
        $pref->data = 50;
        Preferences::shouldReceive('get')->withArgs(['listPageSize', 50])->atLeast()->once()->andReturn($pref);
        Amount::shouldReceive('formatAnything')->andReturn('123');

        $this->be($this->user());
        $this->changeDateRange($this->user(), $range);
        $response = $this->get(route('budgets.index', ['2017-01-01']));
        $response->assertStatus(200);
        // has bread crumb
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers       \FireflyIII\Http\Controllers\Budget\IndexController
     * @dataProvider dateRangeProvider
     *
     * @param string $range
     * @throws Exception
     */
    public function testIndexWithInvalidDate(string $range): void
    {
        $budgetLimit = $this->getRandomBudgetLimit();

        // set budget limit to current month:
        $budgetLimit->start_date = Carbon::now()->startOfMonth();
        $budgetLimit->end_date   = Carbon::now()->endOfMonth();
        $accountRepos            = $this->mock(AccountRepositoryInterface::class);
        $repository              = $this->mock(BudgetRepositoryInterface::class);
        $this->mock(UserRepositoryInterface::class);
        $fiscalHelper = $this->mock(FiscalHelperInterface::class);
        $date         = new Carbon;
        $fiscalHelper->shouldReceive('endOfFiscalYear')->atLeast()->once()->andReturn($date);
        $fiscalHelper->shouldReceive('startOfFiscalYear')->atLeast()->once()->andReturn($date);
        $accountRepos->shouldReceive('getAccountsByType')->andReturn(new Collection);
        $repository->shouldReceive('cleanupBudgets');

        $this->mockDefaultSession();
        Amount::shouldReceive('formatAnything')->andReturn('123');

        $this->be($this->user());
        $this->changeDateRange($this->user(), $range);
        $response = $this->get(route('budgets.index', ['Hello-there']));
        $response->assertStatus(404);
    }

    /**
     * @covers       \FireflyIII\Http\Controllers\Budget\IndexController
     */
    public function testReorder(): void
    {
        $this->mockDefaultSession();

        $pref       = new Preference;
        $pref->data = 50;
        Preferences::shouldReceive('get')->withArgs(['listPageSize', 50])->atLeast()->once()->andReturn($pref);

        $repository = $this->mock(BudgetRepositoryInterface::class);
        $data       = [
            'budgetIds' => [1, 2],
            'page'      => 1,
        ];

        $repository->shouldReceive('cleanupBudgets')->atLeast()->once();
        $repository->shouldReceive('findNull')->atLeast()->once()->andReturn(new Budget);
        $repository->shouldReceive('setBudgetOrder')->atLeast()->once();

        $this->be($this->user());
        $response = $this->post(route('budgets.reorder', $data));
        $response->assertStatus(200);

    }
}
