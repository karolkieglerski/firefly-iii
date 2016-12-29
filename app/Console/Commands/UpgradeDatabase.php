<?php
/**
 * UpgradeDatabase.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace FireflyIII\Console\Commands;


use DB;
use FireflyIII\Models\BudgetLimit;
use FireflyIII\Models\LimitRepetition;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionJournal;
use Illuminate\Console\Command;
use Illuminate\Database\QueryException;
use Log;
use Schema;

/**
 * Class UpgradeDatabase
 *
 * @package FireflyIII\Console\Commands
 */
class UpgradeDatabase extends Command
{

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Will run various commands to update database records.';
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'firefly:upgrade-database';

    /**
     * Create a new command instance.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->setTransactionIdentifier();
        $this->migrateRepetitions();
    }

    private function migrateRepetitions()
    {
        if (!Schema::hasTable('budget_limits')) {
            return;
        }
        // get all budget limits with end_date NULL
        $set = BudgetLimit::whereNull('end_date')->get();
        $this->line(sprintf('Found %d budget limit(s) to update', $set->count()));
        /** @var BudgetLimit $budgetLimit */
        foreach ($set as $budgetLimit) {
            // get limit repetition (should be just one):
            /** @var LimitRepetition $repetition */
            $repetition = $budgetLimit->limitrepetitions()->first();
            if (!is_null($repetition)) {
                $budgetLimit->end_date = $repetition->enddate;
                $budgetLimit->save();
                $this->line(sprintf('Updated budget limit #%d', $budgetLimit->id));
                $repetition->delete();
            }
        }
    }

    /**
     * This is strangely complex, because the HAVING modifier is a no-no. And subqueries in Laravel are weird.
     */
    private function setTransactionIdentifier()
    {
        // if table does not exist, return false
        if (!Schema::hasTable('transaction_journals')) {
            return;
        }


        $subQuery = TransactionJournal::leftJoin('transactions', 'transactions.transaction_journal_id', '=', 'transaction_journals.id')
                                      ->whereNull('transaction_journals.deleted_at')
                                      ->whereNull('transactions.deleted_at')
                                      ->groupBy(['transaction_journals.id'])
                                      ->select(['transaction_journals.id', DB::raw('COUNT(transactions.id) AS t_count')]);

        $result     = DB::table(DB::raw('(' . $subQuery->toSql() . ') AS derived'))
                        ->mergeBindings($subQuery->getQuery())
                        ->where('t_count', '>', 2)
                        ->select(['id', 't_count']);
        $journalIds = array_unique($result->pluck('id')->toArray());

        foreach ($journalIds as $journalId) {
            $this->updateJournal(intval($journalId));
        }
    }

    /**
     * grab all positive transactiosn from this journal that are not deleted. for each one, grab the negative opposing one
     * which has 0 as an identifier and give it the same identifier.
     *
     * @param int $journalId
     */
    private function updateJournal(int $journalId)
    {
        $identifier   = 0;
        $processed    = [];
        $transactions = Transaction::where('transaction_journal_id', $journalId)->where('amount', '>', 0)->get();
        /** @var Transaction $transaction */
        foreach ($transactions as $transaction) {
            // find opposing:
            $amount = bcmul(strval($transaction->amount), '-1');

            try {
                /** @var Transaction $opposing */
                $opposing = Transaction::where('transaction_journal_id', $journalId)
                                       ->where('amount', $amount)->where('identifier', '=', 0)
                                       ->whereNotIn('id', $processed)
                                       ->first();
            } catch (QueryException $e) {
                Log::error($e->getMessage());
                $this->error('Firefly III could not find the "identifier" field in the "transactions" table.');
                $this->error(sprintf('This field is required for Firefly III version %s to run.', config('firefly.version')));
                $this->error('Please run "php artisan migrate" to add this field to the table.');
                $this->info('Then, run "php artisan firefly:upgrade-database" to try again.');

                return;
            }
            if (!is_null($opposing)) {
                // give both a new identifier:
                $transaction->identifier = $identifier;
                $transaction->save();
                $opposing->identifier = $identifier;
                $opposing->save();
                $processed[] = $transaction->id;
                $processed[] = $opposing->id;
                $this->line(sprintf('Database upgrade for journal #%d, transactions #%d and #%d', $journalId, $transaction->id, $opposing->id));
            }
            $identifier++;
        }
    }
}
