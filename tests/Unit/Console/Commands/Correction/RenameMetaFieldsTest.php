<?php
/**
 * RenameMetaFieldsTest.php
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


use FireflyIII\Models\TransactionJournalMeta;
use Log;
use Tests\TestCase;

/**
 * Class RenameMetaFieldsTest
 */
class RenameMetaFieldsTest extends TestCase
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
     * @covers \FireflyIII\Console\Commands\Correction\RenameMetaFields
     */
    public function testHandle(): void
    {
        $this->artisan('firefly-iii:rename-meta-fields')
            ->expectsOutput('All meta fields are correct.')
             ->assertExitCode(0);
    }

    /**
     * @covers \FireflyIII\Console\Commands\Correction\RenameMetaFields
     */
    public function testHandleFixed(): void
    {
        $withdrawal = $this->getRandomWithdrawal();
        $entry      = TransactionJournalMeta::create(
            [
                'transaction_journal_id' => $withdrawal->id,
                'name'                   => 'importHashV2',
                'data'                   => 'Fake data',

            ]
        );

        $this->artisan('firefly-iii:rename-meta-fields')
            ->expectsOutput('Renamed 1 meta field(s).')
             ->assertExitCode(0);

        // verify update
        $this->assertCount(1, TransactionJournalMeta::where('id', $entry->id)->where('name', 'import_hash_v2')->get());
    }

}