<?php
/**
 * ReconcileControllerTest.php
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

namespace Tests\Feature\Controllers\Json;


use Carbon\Carbon;
use FireflyIII\Helpers\Collector\TransactionCollectorInterface;
use FireflyIII\Helpers\FiscalHelperInterface;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Currency\CurrencyRepositoryInterface;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use FireflyIII\Repositories\Recurring\RecurringRepositoryInterface;
use Illuminate\Support\Collection;
use Log;
use Mockery;
use Tests\TestCase;

/**
 *
 * Class ReconcileControllerTest
 */
class ReconcileControllerTest extends TestCase
{
    /**
     *
     */
    public function setUp(): void
    {
        parent::setUp();
        Log::info(sprintf('Now in %s.', \get_class($this)));
    }

    /**
     * Test overview of reconciliation.
     *
     * @covers \FireflyIII\Http\Controllers\Json\ReconcileController
     */
    public function testOverview(): void
    {
        $this->markTestIncomplete('Needs to be rewritten for v4.8.0');

        return;
        $accountRepos   = $this->mock(AccountRepositoryInterface::class);
        $currencyRepos  = $this->mock(CurrencyRepositoryInterface::class);
        $recurringRepos = $this->mock(RecurringRepositoryInterface::class);
        $collector      = $this->mock(TransactionCollectorInterface::class);
        $transactions   = $this->user()->transactions()->inRandomOrder()->take(3)->get();
        $transactions   = $transactions->each(
            function (Transaction $transaction) {
                $transaction->transaction_amount = '5';
            }
        );
        $repository     = $this->mock(JournalRepositoryInterface::class);
        $repository->shouldReceive('firstNull')->andReturn(new TransactionJournal);
        $repository->shouldReceive('getTransactionsById')->andReturn($transactions)->twice();

        $fiscalHelper = $this->mock(FiscalHelperInterface::class);
        $date         = new Carbon;
        $fiscalHelper->shouldReceive('endOfFiscalYear')->atLeast()->once()->andReturn($date);
        $fiscalHelper->shouldReceive('startOfFiscalYear')->atLeast()->once()->andReturn($date);

        $accountRepos->shouldReceive('findNull')->andReturn($this->getRandomAsset())->atLeast()->once();
        $accountRepos->shouldReceive('getMetaValue')->atLeast()->once()->andReturn(1);

        $parameters = [
            'startBalance' => '0',
            'endBalance'   => '10',
            'transactions' => [1, 2, 3],
            'cleared'      => [4, 5, 6],
        ];
        $this->be($this->user());
        $response = $this->get(route('accounts.reconcile.overview', [1, '20170101', '20170131']) . '?' . http_build_query($parameters));
        $response->assertStatus(200);
    }

    /**
     * Test overview when it's not an asset.
     *
     * @covers                   \FireflyIII\Http\Controllers\Json\ReconcileController
     */
    public function testOverviewNotAsset(): void
    {
        $this->markTestIncomplete('Needs to be rewritten for v4.8.0');

        return;
        $accountRepos   = $this->mock(AccountRepositoryInterface::class);
        $currencyRepos  = $this->mock(CurrencyRepositoryInterface::class);
        $recurringRepos = $this->mock(RecurringRepositoryInterface::class);
        $fiscalHelper   = $this->mock(FiscalHelperInterface::class);
        $collector      = $this->mock(TransactionCollectorInterface::class);
        $date           = new Carbon;
        $fiscalHelper->shouldReceive('endOfFiscalYear')->atLeast()->once()->andReturn($date);
        $fiscalHelper->shouldReceive('startOfFiscalYear')->atLeast()->once()->andReturn($date);
        $account    = $this->user()->accounts()->where('account_type_id', '!=', 3)->first();
        $parameters = [
            'startBalance' => '0',
            'endBalance'   => '10',
            'transactions' => [1, 2, 3],
            'cleared'      => [4, 5, 6],
        ];
        $this->be($this->user());
        $response = $this->get(route('accounts.reconcile.overview', [$account->id, '20170101', '20170131']) . '?' . http_build_query($parameters));
        $response->assertStatus(500);
    }


    /**
     * List transactions for reconciliation view.
     *
     * @covers \FireflyIII\Http\Controllers\Json\ReconcileController
     */
    public function testTransactions(): void
    {
        $this->markTestIncomplete('Needs to be rewritten for v4.8.0');

        return;
        $repository     = $this->mock(CurrencyRepositoryInterface::class);
        $accountRepos   = $this->mock(AccountRepositoryInterface::class);
        $recurringRepos = $this->mock(RecurringRepositoryInterface::class);
        $fiscalHelper   = $this->mock(FiscalHelperInterface::class);
        $collector      = $this->mock(TransactionCollectorInterface::class);
        $date           = new Carbon;
        $fiscalHelper->shouldReceive('endOfFiscalYear')->atLeast()->once()->andReturn($date);
        $fiscalHelper->shouldReceive('startOfFiscalYear')->atLeast()->once()->andReturn($date);
        $accountRepos->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'currency_id'])->andReturn('1')->atLeast()->once();
        $repository->shouldReceive('findNull')->once()->andReturn(TransactionCurrency::find(1));

        $collector->shouldReceive('setAccounts')->atLeast()->once()->andReturnSelf();
        $collector->shouldReceive('setRange')->atLeast()->once()->andReturnSelf();
        $collector->shouldReceive('withBudgetInformation')->atLeast()->once()->andReturnSelf();
        $collector->shouldReceive('withOpposingAccount')->atLeast()->once()->andReturnSelf();
        $collector->shouldReceive('withCategoryInformation')->atLeast()->once()->andReturnSelf();
        $collector->shouldReceive('getTransactions')->atLeast()->once()->andReturn(new Collection);


        $this->be($this->user());
        $response = $this->get(route('accounts.reconcile.transactions', [1, '20170101', '20170131']));
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Json\ReconcileController
     */
    public function testTransactionsInitialBalance(): void
    {
        $this->markTestIncomplete('Needs to be rewritten for v4.8.0');

        return;
        $accountRepos   = $this->mock(AccountRepositoryInterface::class);
        $currencyRepos  = $this->mock(CurrencyRepositoryInterface::class);
        $recurringRepos = $this->mock(RecurringRepositoryInterface::class);
        $fiscalHelper   = $this->mock(FiscalHelperInterface::class);
        $collector      = $this->mock(TransactionCollectorInterface::class);
        $date           = new Carbon;
        $fiscalHelper->shouldReceive('endOfFiscalYear')->atLeast()->once()->andReturn($date);
        $fiscalHelper->shouldReceive('startOfFiscalYear')->atLeast()->once()->andReturn($date);

        $transaction = Transaction::leftJoin('accounts', 'accounts.id', '=', 'transactions.account_id')
                                  ->where('accounts.user_id', $this->user()->id)->where('accounts.account_type_id', 6)->first(['account_id']);
        $this->be($this->user());
        $response = $this->get(route('accounts.reconcile.transactions', [$transaction->account_id, '20170101', '20170131']));
        $response->assertStatus(302);
    }


}
