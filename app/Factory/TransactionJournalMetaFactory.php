<?php
/**
 * TransactionJournalMetaFactory.php
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
/** @noinspection MultipleReturnStatementsInspection */

declare(strict_types=1);

namespace FireflyIII\Factory;

use Carbon\Carbon;
use Exception;
use FireflyIII\Models\TransactionJournalMeta;
use Log;

/**
 * Class TransactionJournalMetaFactory
 */
class TransactionJournalMetaFactory
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        if ('testing' === config('app.env')) {
            Log::warning(sprintf('%s should not be instantiated in the TEST environment!', get_class($this)));
        }
    }

    /**
     * @param array $data
     *
     * @return TransactionJournalMeta|null
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function updateOrCreate(array $data): ?TransactionJournalMeta
    {
        Log::debug('In updateOrCreate()');
        $value = $data['data'];
        /** @var TransactionJournalMeta $entry */
        $entry = $data['journal']->transactionJournalMeta()->where('name', $data['name'])->first();
        if (null === $value && null !== $entry) {
            Log::debug('Value is empty, delete meta value.');
            try {
                $entry->delete();
            } catch (Exception $e) { // @codeCoverageIgnore
                Log::error(sprintf('Could not delete transaction journal meta: %s', $e->getMessage())); // @codeCoverageIgnore
            }

            return null;
        }

        if ($data['data'] instanceof Carbon) {
            Log::debug('Is a carbon object.');
            $value = $data['data']->toW3cString();
        }
        if ('' === (string)$value) {
            Log::debug('Is an empty string.');
            // don't store blank strings.
            if (null !== $entry) {
                Log::debug('Will not store empty strings, delete meta value');
                try {
                    $entry->delete();
                } catch (Exception $e) { // @codeCoverageIgnore
                    Log::error(sprintf('Could not delete transaction journal meta: %s', $e->getMessage())); // @codeCoverageIgnore
                }
            }

            return null;
        }

        if (null === $entry) {
            Log::debug('Will create new object.');
            Log::debug(sprintf('Going to create new meta-data entry to store "%s".', $data['name']));
            $entry = new TransactionJournalMeta();
            $entry->transactionJournal()->associate($data['journal']);
            $entry->name = $data['name'];
        }
        Log::debug('Will update value and return.');
        $entry->data = $value;
        $entry->save();

        return $entry;
    }

}
