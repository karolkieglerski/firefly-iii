<?php
/**
 * TransactionJournalSupport.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace FireflyIII\Support\Models;


use Carbon\Carbon;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Support\CacheProperties;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Class TransactionJournalSupport
 *
 * @package FireflyIII\Support\Models
 * @mixin \Eloquent
 */
class TransactionJournalSupport extends Model
{

    /**
     * @param TransactionJournal $journal
     *
     * @return string
     * @throws FireflyException
     */
    public static function amount(TransactionJournal $journal): string
    {
        $cache = new CacheProperties;
        $cache->addProperty($journal->id);
        $cache->addProperty('transaction-journal');
        $cache->addProperty('amount');
        if ($cache->has()) {
            return $cache->get();
        }

        // saves on queries:
        $amount = $journal->transactions()->where('amount', '>', 0)->get()->sum('amount');

        if ($journal->isWithdrawal()) {
            $amount = $amount * -1;
        }
        $amount = strval($amount);
        $cache->store($amount);

        return $amount;
    }

    /**
     * @param TransactionJournal $journal
     *
     * @return string
     */
    public static function amountPositive(TransactionJournal $journal): string
    {
        $cache = new CacheProperties;
        $cache->addProperty($journal->id);
        $cache->addProperty('transaction-journal');
        $cache->addProperty('amount-positive');
        if ($cache->has()) {
            return $cache->get();
        }

        // saves on queries:
        $amount = $journal->transactions()->where('amount', '>', 0)->get()->sum('amount');

        $amount = strval($amount);
        $cache->store($amount);

        return $amount;
    }

    /**
     * @param TransactionJournal $journal
     *
     * @return int
     */
    public static function budgetId(TransactionJournal $journal): int
    {
        $budget = $journal->budgets()->first();
        if (!is_null($budget)) {
            return $budget->id;
        }

        return 0;
    }

    /**
     * @param TransactionJournal $journal
     *
     * @return string
     */
    public static function categoryAsString(TransactionJournal $journal): string
    {
        $category = $journal->categories()->first();
        if (!is_null($category)) {
            return $category->name;
        }

        return '';
    }

    /**
     * @param TransactionJournal $journal
     * @param string             $dateField
     *
     * @return string
     */
    public static function dateAsString(TransactionJournal $journal, string $dateField = ''): string
    {
        if ($dateField === '') {
            return $journal->date->format('Y-m-d');
        }
        if (!is_null($journal->$dateField) && $journal->$dateField instanceof Carbon) {
            // make field NULL
            $carbon              = clone $journal->$dateField;
            $journal->$dateField = null;
            $journal->save();

            // create meta entry
            $journal->setMeta($dateField, $carbon);

            // return that one instead.
            return $carbon->format('Y-m-d');
        }
        $metaField = $journal->getMeta($dateField);
        if (!is_null($metaField)) {
            $carbon = new Carbon($metaField);

            return $carbon->format('Y-m-d');
        }

        return '';


    }

    /**
     * @param TransactionJournal $journal
     *
     * @return Collection
     */
    public static function destinationAccountList(TransactionJournal $journal): Collection
    {
        $cache = new CacheProperties;
        $cache->addProperty($journal->id);
        $cache->addProperty('transaction-journal');
        $cache->addProperty('destination-account-list');
        if ($cache->has()) {
            return $cache->get();
        }
        $transactions = $journal->transactions()->where('amount', '>', 0)->orderBy('transactions.account_id')->with('account')->get();
        $list         = new Collection;
        /** @var Transaction $t */
        foreach ($transactions as $t) {
            $list->push($t->account);
        }
        $cache->store($list);

        return $list;
    }

    /**
     * @param TransactionJournal $journal
     *
     * @return Collection
     */
    public static function destinationTransactionList(TransactionJournal $journal): Collection
    {
        $cache = new CacheProperties;
        $cache->addProperty($journal->id);
        $cache->addProperty('transaction-journal');
        $cache->addProperty('destination-transaction-list');
        if ($cache->has()) {
            return $cache->get();
        }
        $list = $journal->transactions()->where('amount', '>', 0)->with('account')->get();
        $cache->store($list);

        return $list;
    }

    /**
     * @param Builder $query
     * @param string  $table
     *
     * @return bool
     */
    public static function isJoined(Builder $query, string $table): bool
    {
        $joins = $query->getQuery()->joins;
        if (is_null($joins)) {
            return false;
        }
        foreach ($joins as $join) {
            if ($join->table === $table) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param TransactionJournal $journal
     *
     * @return int
     */
    public static function piggyBankId(TransactionJournal $journal): int
    {
        if ($journal->piggyBankEvents()->count() > 0) {
            return $journal->piggyBankEvents()->orderBy('date', 'DESC')->first()->piggy_bank_id;
        }

        return 0;
    }

    /**
     * @return array
     */
    public static function queryFields(): array
    {
        return [
            'transaction_journals.*',
            'transaction_types.type AS transaction_type_type', // the other field is called "transaction_type_id" so this is pretty consistent.
            'transaction_currencies.code AS transaction_currency_code',
        ];
    }

    /**
     * @param TransactionJournal $journal
     *
     * @return Collection
     */
    public static function sourceAccountList(TransactionJournal $journal): Collection
    {
        $cache = new CacheProperties;
        $cache->addProperty($journal->id);
        $cache->addProperty('transaction-journal');
        $cache->addProperty('source-account-list');
        if ($cache->has()) {
            return $cache->get();
        }
        $transactions = $journal->transactions()->where('amount', '<', 0)->orderBy('transactions.account_id')->with('account')->get();
        $list         = new Collection;
        /** @var Transaction $t */
        foreach ($transactions as $t) {
            $list->push($t->account);
        }
        $cache->store($list);

        return $list;
    }

    /**
     * @param TransactionJournal $journal
     *
     * @return Collection
     */
    public static function sourceTransactionList(TransactionJournal $journal): Collection
    {
        $cache = new CacheProperties;
        $cache->addProperty($journal->id);
        $cache->addProperty('transaction-journal');
        $cache->addProperty('source-transaction-list');
        if ($cache->has()) {
            return $cache->get();
        }
        $list = $journal->transactions()->where('amount', '<', 0)->with('account')->get();
        $cache->store($list);

        return $list;
    }

    /**
     * @param TransactionJournal $journal
     *
     * @return string
     */
    public static function transactionTypeStr(TransactionJournal $journal): string
    {
        $cache = new CacheProperties;
        $cache->addProperty($journal->id);
        $cache->addProperty('transaction-journal');
        $cache->addProperty('type-string');
        if ($cache->has()) {
            return $cache->get();
        }

        $typeStr = $journal->transaction_type_type ?? $journal->transactionType->type;
        $cache->store($typeStr);

        return $typeStr;
    }
}
