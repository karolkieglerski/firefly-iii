<?php
/**
 * DeleteEmptyJournalsTest.php
 * Copyright (c) 2019 thegrumpydictator@gmail.com
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

namespace Tests\Unit\Console\Commands\Correction;


use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionJournal;
use Log;
use Tests\TestCase;

class DeleteEmptyJournalsTest extends TestCase
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
     * @covers \FireflyIII\Console\Commands\Correction\DeleteEmptyJournals
     */
    public function testHandle(): void
    {
        // assume there are no empty journals or uneven journals
        $this->artisan('firefly-iii:delete-empty-journals')
             ->expectsOutput('No uneven transaction journals.')
             ->expectsOutput('No empty transaction journals.')
             ->assertExitCode(0);
    }

    /**
     * @covers \FireflyIII\Console\Commands\Correction\DeleteEmptyJournals
     */
    public function testHandleEmptyJournals(): void
    {
        // create empty journal:
        $journal = TransactionJournal::create(
            [
                'user_id'                 => 1,
                'transaction_currency_id' => 1,
                'transaction_type_id'     => 1,
                'description'             => 'Hello',
                'tag_count'               => 0,
                'date'                    => '2019-01-01',
            ]
        );
        $this->artisan('firefly-iii:delete-empty-journals')
             ->expectsOutput('No uneven transaction journals.')
             ->expectsOutput(sprintf('Deleted empty transaction journal #%d', $journal->id))
             ->assertExitCode(0);

        // verify its indeed gone
        $this->assertCount(0, TransactionJournal::where('id', $journal->id)->whereNull('deleted_at')->get());
    }

    /**
     * @covers \FireflyIII\Console\Commands\Correction\DeleteEmptyJournals
     */
    public function testHandleUnevenJournals(): void
    {
        // create empty journal:
        $journal = TransactionJournal::create(
            [
                'user_id'                 => 1,
                'transaction_currency_id' => 1,
                'transaction_type_id'     => 1,
                'description'             => 'Hello',
                'tag_count'               => 0,
                'date'                    => '2019-01-01',
            ]
        );

        // link empty transaction
        $transaction = Transaction::create(
            [
                'transaction_journal_id' => $journal->id,
                'account_id'             => 1,
                'amount'                 => '5',
            ]
        );


        $this->artisan('firefly-iii:delete-empty-journals')
             ->expectsOutput(sprintf('Deleted transaction journal #%d because it had an uneven number of transactions.', $journal->id))
            ->expectsOutput('No empty transaction journals.')
             ->assertExitCode(0);

        // verify both are gone
        $this->assertCount(0, TransactionJournal::where('id', $journal->id)->whereNull('deleted_at')->get());
        $this->assertCount(0, Transaction::where('id', $transaction->id)->whereNull('deleted_at')->get());
    }


}