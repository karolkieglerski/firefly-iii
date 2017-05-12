<?php
/**
 * TransactionMatcher.php
 * Copyright (C) 2016 Robert Horlings
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

namespace FireflyIII\Rules;

use FireflyIII\Helpers\Collector\JournalCollectorInterface;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionType;
use FireflyIII\Repositories\Journal\JournalTaskerInterface;
use Illuminate\Support\Collection;
use Log;

/**
 * Class TransactionMatcher is used to find a list of
 * transaction matching a set of triggers
 *
 * @package FireflyIII\Rules
 */
class TransactionMatcher
{
    /** @var int */
    private $limit = 10;
    /** @var int Maximum number of transaction to search in (for performance reasons) * */
    private $range = 200;
    /** @var  JournalTaskerInterface */
    private $tasker;
    /** @var array */
    private $transactionTypes = [TransactionType::DEPOSIT, TransactionType::WITHDRAWAL, TransactionType::TRANSFER];
    /** @var array List of triggers to match */
    private $triggers = [];

    /**
     * TransactionMatcher constructor. Typehint the repository.
     *
     * @param JournalTaskerInterface $tasker
     */
    public function __construct(JournalTaskerInterface $tasker)
    {
        $this->tasker = $tasker;

    }

    /**
     * This method will search the user's transaction journal (with an upper limit of $range) for
     * transaction journals matching the given $triggers. This is accomplished by trying to fire these
     * triggers onto each transaction journal until enough matches are found ($limit).
     *
     * @return Collection
     *
     */
    public function findMatchingTransactions(): Collection
    {
        if (count($this->triggers) === 0) {
            return new Collection;
        }
        $pageSize = min($this->range / 2, $this->limit * 2);

        // Variables used within the loop
        $processed = 0;
        $page      = 1;
        $result    = new Collection();
        $processor = Processor::makeFromStringArray($this->triggers);

        // Start a loop to fetch batches of transactions. The loop will finish if:
        //   - all transactions have been fetched from the database
        //   - the maximum number of transactions to return has been found
        //   - the maximum number of transactions to search in have been searched 
        do {
            // Fetch a batch of transactions from the database
            /** @var JournalCollectorInterface $collector */
            $collector = app(JournalCollectorInterface::class);
            $collector->setUser(auth()->user());
            $collector->setAllAssetAccounts()->setLimit($pageSize)->setPage($page)->setTypes($this->transactionTypes);
            $set = $collector->getPaginatedJournals();
            Log::debug(sprintf('Found %d journals to check. ', $set->count()));

            // Filter transactions that match the given triggers.
            $filtered = $set->filter(
                function (Transaction $transaction) use ($processor) {
                    Log::debug(sprintf('Test these triggers on journal #%d (transaction #%d)', $transaction->transaction_journal_id, $transaction->id));

                    return $processor->handleTransaction($transaction);
                }
            );

            Log::debug(sprintf('Found %d journals that match.', $filtered->count()));

            // merge:
            /** @var Collection $result */
            $result = $result->merge($filtered);
            Log::debug(sprintf('Total count is now %d', $result->count()));

            // Update counters
            $page++;
            $processed += count($set);

            Log::debug(sprintf('Page is now %d, processed is %d', $page, $processed));

            // Check for conditions to finish the loop
            $reachedEndOfList = $set->count() < 1;
            $foundEnough      = $result->count() >= $this->limit;
            $searchedEnough   = ($processed >= $this->range);

            Log::debug(sprintf('reachedEndOfList: %s', var_export($reachedEndOfList, true)));
            Log::debug(sprintf('foundEnough: %s', var_export($foundEnough, true)));
            Log::debug(sprintf('searchedEnough: %s', var_export($searchedEnough, true)));

        } while (!$reachedEndOfList && !$foundEnough && !$searchedEnough);

        // If the list of matchingTransactions is larger than the maximum number of results
        // (e.g. if a large percentage of the transactions match), truncate the list
        $result = $result->slice(0, $this->limit);

        return $result;
    }

    /**
     * @return int
     */
    public function getLimit(): int
    {
        return $this->limit;
    }

    /**
     * @param int $limit
     *
     * @return TransactionMatcher
     */
    public function setLimit(int $limit): TransactionMatcher
    {
        $this->limit = $limit;

        return $this;
    }

    /**
     * @return int
     */
    public function getRange(): int
    {
        return $this->range;
    }

    /**
     * @param int $range
     *
     * @return TransactionMatcher
     */
    public function setRange(int $range): TransactionMatcher
    {
        $this->range = $range;

        return $this;

    }

    /**
     * @return array
     */
    public function getTriggers(): array
    {
        return $this->triggers;
    }

    /**
     * @param array $triggers
     *
     * @return TransactionMatcher
     */
    public function setTriggers(array $triggers): TransactionMatcher
    {
        $this->triggers = $triggers;

        return $this;
    }


}
