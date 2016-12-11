<?php
/**
 * TestData.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types = 1);
namespace FireflyIII\Support\Migration;

use Carbon\Carbon;
use Crypt;
use DB;
use Navigation;
use Storage;

/**
 * Class TestData
 *
 * @package FireflyIII\Support\Migration
 */
class TestData
{
    /** @var array */
    private $data = [];
    /** @var Carbon */
    private $end;
    /** @var Carbon */
    private $start;

    /** @var  string */
    private $time;

    /**
     * TestData constructor.
     *
     * @param array $data
     */
    private function __construct(array $data)
    {
        $this->data = $data;
        $start      = new Carbon;
        $start->startOfYear();
        $start->subYears(2);
        $end = new Carbon;

        $this->start = $start;
        $this->end   = $end;
        $this->time  = $end->format('Y-m-d H:i:s');
    }

    /**
     * @param array $data
     */
    public static function run(array $data)
    {
        $seeder = new TestData($data);
        $seeder->go();
    }

    /**
     *
     */
    private function createAccounts()
    {
        $insert = [];
        foreach ($this->data['accounts'] as $account) {
            $insert[] = [
                'created_at'      => $this->time,
                'updated_at'      => $this->time,
                'user_id'         => $account['user_id'],
                'account_type_id' => $account['account_type_id'],
                'name'            => $account['name'],
                'active'          => 1,
                'encrypted'       => 0,
                'virtual_balance' => 0,
                'iban'            => isset($account['iban']) ? Crypt::encrypt($account['iban']) : null,
            ];
        }
        DB::table('accounts')->insert($insert);
        $insert = [];
        foreach ($this->data['account-meta'] as $meta) {
            $insert[] = [
                'created_at' => $this->time,
                'updated_at' => $this->time,
                'account_id' => $meta['account_id'],
                'name'       => $meta['name'],
                'data'       => $meta['data'],
            ];
        }
        DB::table('account_meta')->insert($insert);
    }

    /**
     *
     */
    private function createAttachments()
    {
        $disk = Storage::disk('upload');
        foreach ($this->data['attachments'] as $attachment) {
            $data         = Crypt::encrypt($attachment['content']);
            $attachmentId = DB::table('attachments')->insertGetId(
                [
                    'created_at'      => $this->time,
                    'updated_at'      => $this->time,
                    'attachable_id'   => $attachment['attachable_id'],
                    'attachable_type' => $attachment['attachable_type'],
                    'user_id'         => $attachment['user_id'],
                    'md5'             => md5($attachment['content']),
                    'filename'        => Crypt::encrypt($attachment['filename']),
                    'title'           => Crypt::encrypt($attachment['title']),
                    'description'     => Crypt::encrypt($attachment['description']),
                    'notes'           => Crypt::encrypt($attachment['notes']),
                    'mime'            => Crypt::encrypt($attachment['mime']),
                    'size'            => strlen($attachment['content']),
                    'uploaded'        => 1,
                ]
            );

            $disk->put('at-' . $attachmentId . '.data', $data);
        }
    }

    /**
     *
     */
    private function createBills()
    {
        $insert = [];
        foreach ($this->data['bills'] as $bill) {
            $insert[] = [
                'created_at'      => $this->time,
                'updated_at'      => $this->time,
                'user_id'         => $bill['user_id'],
                'name'            => Crypt::encrypt($bill['name']),
                'match'           => Crypt::encrypt($bill['match']),
                'amount_min'      => $bill['amount_min'],
                'amount_max'      => $bill['amount_max'],
                'date'            => $bill['date'],
                'active'          => $bill['active'],
                'automatch'       => $bill['automatch'],
                'repeat_freq'     => $bill['repeat_freq'],
                'skip'            => $bill['skip'],
                'name_encrypted'  => 1,
                'match_encrypted' => 1,
            ];
        }
        DB::table('bills')->insert($insert);
    }

    /**
     *
     */
    private function createBudgets()
    {
        $insert = [];
        foreach ($this->data['budgets'] as $budget) {
            $insert[] = [
                'created_at' => $this->time,
                'updated_at' => $this->time,
                'user_id'    => $budget['user_id'],
                'name'       => Crypt::encrypt($budget['name']),
                'encrypted'  => 1,
            ];
        }
        DB::table('budgets')->insert($insert);

        foreach ($this->data['budget-limits'] as $limit) {
            $amount  = rand($limit['amount_min'], $limit['amount_max']);
            $limitId = DB::table('budget_limits')->insertGetId(
                [
                    'created_at'  => $this->time,
                    'updated_at'  => $this->time,
                    'budget_id'   => $limit['budget_id'],
                    'startdate'   => $limit['startdate'],
                    'amount'      => $amount,
                    'repeats'     => 0,
                    'repeat_freq' => $limit['repeat_freq'],
                ]
            );

            DB::table('limit_repetitions')->insert(
                [
                    'created_at'      => $this->time,
                    'updated_at'      => $this->time,
                    'budget_limit_id' => $limitId,
                    'startdate'       => $limit['startdate'],
                    'enddate'         => Navigation::endOfPeriod(new Carbon($limit['startdate']), $limit['repeat_freq'])->format('Y-m-d'),
                    'amount'          => $amount,
                ]
            );
        }
        $current = clone $this->start;
        while ($current <= $this->end) {
            foreach ($this->data['monthly-limits'] as $limit) {
                $amount  = rand($limit['amount_min'], $limit['amount_max']);
                $limitId = DB::table('budget_limits')->insertGetId(
                    [
                        'created_at'  => $this->time,
                        'updated_at'  => $this->time,
                        'budget_id'   => $limit['budget_id'],
                        'startdate'   => $current->format('Y-m-d'),
                        'amount'      => $amount,
                        'repeats'     => 0,
                        'repeat_freq' => 'monthly',
                    ]
                );

                DB::table('limit_repetitions')->insert(
                    [
                        'created_at'      => $this->time,
                        'updated_at'      => $this->time,
                        'budget_limit_id' => $limitId,
                        'startdate'       => $current->format('Y-m-d'),
                        'enddate'         => Navigation::endOfPeriod($current, 'monthly')->format('Y-m-d'),
                        'amount'          => $amount,
                    ]
                );
            }

            $current->addMonth();
        }

    }

    /**
     *
     */
    private function createCategories()
    {
        $insert = [];
        foreach ($this->data['categories'] as $category) {
            $insert[] = [
                'created_at' => $this->time,
                'updated_at' => $this->time,
                'user_id'    => $category['user_id'],
                'name'       => Crypt::encrypt($category['name']),
                'encrypted'  => 1,
            ];
        }
        DB::table('categories')->insert($insert);
    }

    /**
     *
     */
    private function createCurrencies()
    {
        $insert = [];
        foreach ($this->data['currencies'] as $job) {
            $insert[] = [
                'created_at' => $this->time,
                'updated_at' => $this->time,
                'deleted_at' => null,
                'code'       => $job['code'],
                'name'       => $job['name'],
                'symbol'     => $job['symbol'],
            ];
        }
        DB::table('transaction_currencies')->insert($insert);
    }

    /**
     *
     */
    private function createExportJobs()
    {
        $insert = [];
        $disk   = Storage::disk('export');
        foreach ($this->data['export-jobs'] as $job) {
            $insert[] = [
                'created_at' => $this->time,
                'updated_at' => $this->time,
                'user_id'    => $job['user_id'],
                'key'        => $job['key'],
                'status'     => $job['status'],
            ];
            $disk->put($job['key'] . '.zip', 'Nonsense data for "ziP" file.');
        }
        DB::table('export_jobs')->insert($insert);

        // store fake export file:

    }

    /**
     *
     */
    private function createImportJobs()
    {
        $insert = [];
        foreach ($this->data['import-jobs'] as $job) {
            $insert[] = [
                'created_at'      => $this->time,
                'updated_at'      => $this->time,
                'user_id'         => $job['user_id'],
                'file_type'       => $job['file_type'],
                'key'             => $job['key'],
                'status'          => $job['status'],
                'extended_status' => json_encode($job['extended_status']),
                'configuration'   => json_encode($job['configuration']),
            ];
        }
        DB::table('import_jobs')->insert($insert);
    }

    /**
     *
     */
    private function createJournals()
    {
        $current      = clone $this->start;
        $transactions = [];
        while ($current <= $this->end) {
            $date  = $current->format('Y-m-');
            $month = $current->format('F');

            // run all monthly withdrawals:
            foreach ($this->data['monthly-withdrawals'] as $withdrawal) {
                $description    = str_replace(':month', $month, $withdrawal['description']);
                $journalId      = DB::table('transaction_journals')->insertGetId(
                    [
                        'created_at'              => $this->time,
                        'updated_at'              => $this->time,
                        'user_id'                 => $withdrawal['user_id'],
                        'transaction_type_id'     => 1,
                        'bill_id'                 => $withdrawal['bill_id'] ?? null,
                        'transaction_currency_id' => 1,
                        'description'             => Crypt::encrypt($description),
                        'completed'               => 1,
                        'date'                    => $date . $withdrawal['day-of-month'],
                        'interest_date'           => $withdrawal['interest_date'] ?? null,
                        'book_date'               => $withdrawal['book_date'] ?? null,
                        'process_date'            => $withdrawal['process_date'] ?? null,
                        'encrypted'               => 1,
                        'order'                   => 0,
                        'tag_count'               => 0,
                    ]
                );
                $amount         = (rand($withdrawal['min_amount'] * 100, $withdrawal['max_amount'] * 100)) / 100;
                $transactions[] = [
                    'created_at'             => $this->time,
                    'updated_at'             => $this->time,
                    'transaction_journal_id' => $journalId,
                    'account_id'             => $withdrawal['source_id'],
                    'amount'                 => $amount * -1,
                ];
                $transactions[] = [
                    'created_at'             => $this->time,
                    'updated_at'             => $this->time,
                    'transaction_journal_id' => $journalId,
                    'account_id'             => $withdrawal['destination_id'],
                    'amount'                 => $amount,
                ];

                // link to budget if set.
                if (isset($withdrawal['budget_id'])) {
                    DB::table('budget_transaction_journal')->insert(
                        [
                            'budget_id'              => $withdrawal['budget_id'],
                            'transaction_journal_id' => $journalId,

                        ]
                    );
                }
                // link to category if set.
                if (isset($withdrawal['category_id'])) {
                    DB::table('category_transaction_journal')->insert(
                        [
                            'category_id'            => $withdrawal['category_id'],
                            'transaction_journal_id' => $journalId,

                        ]
                    );
                }
            }

            // run all monthly deposits:
            foreach ($this->data['monthly-deposits'] as $deposit) {
                $description    = str_replace(':month', $month, $deposit['description']);
                $journalId      = DB::table('transaction_journals')->insertGetId(
                    [
                        'created_at'              => $this->time,
                        'updated_at'              => $this->time,
                        'user_id'                 => $deposit['user_id'],
                        'transaction_type_id'     => 2,
                        'bill_id'                 => $deposit['bill_id'] ?? null,
                        'transaction_currency_id' => 1,
                        'description'             => Crypt::encrypt($description),
                        'completed'               => 1,
                        'date'                    => $date . $deposit['day-of-month'],
                        'interest_date'           => $deposit['interest_date'] ?? null,
                        'book_date'               => $deposit['book_date'] ?? null,
                        'process_date'            => $deposit['process_date'] ?? null,
                        'encrypted'               => 1,
                        'order'                   => 0,
                        'tag_count'               => 0,
                    ]
                );
                $amount         = (rand($deposit['min_amount'] * 100, $deposit['max_amount'] * 100)) / 100;
                $transactions[] = [
                    'created_at'             => $this->time,
                    'updated_at'             => $this->time,
                    'transaction_journal_id' => $journalId,
                    'account_id'             => $deposit['source_id'],
                    'amount'                 => $amount * -1,
                ];
                $transactions[] = [
                    'created_at'             => $this->time,
                    'updated_at'             => $this->time,
                    'transaction_journal_id' => $journalId,
                    'account_id'             => $deposit['destination_id'],
                    'amount'                 => $amount,
                ];

                // link to category if set.
                if (isset($deposit['category_id'])) {
                    DB::table('category_transaction_journal')->insert(
                        [
                            'category_id'            => $deposit['category_id'],
                            'transaction_journal_id' => $journalId,

                        ]
                    );
                }
            }
            // run all monthly transfers:
            foreach ($this->data['monthly-transfers'] as $transfer) {
                $description    = str_replace(':month', $month, $transfer['description']);
                $journalId      = DB::table('transaction_journals')->insertGetId(
                    [
                        'created_at'              => $this->time,
                        'updated_at'              => $this->time,
                        'user_id'                 => $transfer['user_id'],
                        'transaction_type_id'     => 3,
                        'bill_id'                 => $transfer['bill_id'] ?? null,
                        'transaction_currency_id' => 1,
                        'description'             => Crypt::encrypt($description),
                        'completed'               => 1,
                        'date'                    => $date . $transfer['day-of-month'],
                        'interest_date'           => $transfer['interest_date'] ?? null,
                        'book_date'               => $transfer['book_date'] ?? null,
                        'process_date'            => $transfer['process_date'] ?? null,
                        'encrypted'               => 1,
                        'order'                   => 0,
                        'tag_count'               => 0,
                    ]
                );
                $amount         = (rand($transfer['min_amount'] * 100, $transfer['max_amount'] * 100)) / 100;
                $transactions[] = [
                    'created_at'             => $this->time,
                    'updated_at'             => $this->time,
                    'transaction_journal_id' => $journalId,
                    'account_id'             => $transfer['source_id'],
                    'amount'                 => $amount * -1,
                ];
                $transactions[] = [
                    'created_at'             => $this->time,
                    'updated_at'             => $this->time,
                    'transaction_journal_id' => $journalId,
                    'account_id'             => $transfer['destination_id'],
                    'amount'                 => $amount,
                ];
                // link to category if set.
                if (isset($transfer['category_id'])) {
                    DB::table('category_transaction_journal')->insert(
                        [
                            'category_id'            => $transfer['category_id'],
                            'transaction_journal_id' => $journalId,

                        ]
                    );
                }
            }
            DB::table('transactions')->insert($transactions);
            $transactions = [];
            $current->addMonth();
        }


    }

    /**
     *
     */
    private function createMultiDeposits()
    {
        foreach ($this->data['multi-deposits'] as $deposit) {
            $journalId  = DB::table('transaction_journals')->insertGetId(
                [
                    'created_at'              => $this->time,
                    'updated_at'              => $this->time,
                    'user_id'                 => $deposit['user_id'],
                    'transaction_type_id'     => 2,
                    'transaction_currency_id' => 1,
                    'description'             => Crypt::encrypt($deposit['description']),
                    'completed'               => 1,
                    'date'                    => $deposit['date'],
                    'interest_date'           => $deposit['interest_date'] ?? null,
                    'book_date'               => $deposit['book_date'] ?? null,
                    'process_date'            => $deposit['process_date'] ?? null,
                    'encrypted'               => 1,
                    'order'                   => 0,
                    'tag_count'               => 0,
                ]
            );
            $identifier = 0;
            foreach ($deposit['source_ids'] as $index => $source) {
                $description = $deposit['description'] . ' (#' . ($index + 1) . ')';
                $amount      = $deposit['amounts'][$index];
                $first       = DB::table('transactions')->insertGetId(
                    [
                        'created_at'             => $this->time,
                        'updated_at'             => $this->time,
                        'account_id'             => $deposit['destination_id'],
                        'transaction_journal_id' => $journalId,
                        'description'            => $description,
                        'amount'                 => $amount,
                        'identifier'             => $identifier,
                    ]
                );
                $second      = DB::table('transactions')->insertGetId(
                    [
                        'created_at'             => $this->time,
                        'updated_at'             => $this->time,
                        'account_id'             => $source,
                        'transaction_journal_id' => $journalId,
                        'description'            => $description,
                        'amount'                 => $amount * -1,
                        'identifier'             => $identifier,
                    ]
                );
                $identifier++;
                // link first and second to budget and category, if present.

                if (isset($deposit['category_ids'][$index])) {
                    DB::table('category_transaction')->insert(
                        [
                            'category_id'    => $deposit['category_ids'][$index],
                            'transaction_id' => $first,
                        ]
                    );
                    DB::table('category_transaction')->insert(
                        [
                            'category_id'    => $deposit['category_ids'][$index],
                            'transaction_id' => $second,
                        ]
                    );
                }
            }
        }
    }

    private function createMultiTransfers()
    {
        foreach ($this->data['multi-transfers'] as $transfer) {
            $journalId  = DB::table('transaction_journals')->insertGetId(
                [
                    'created_at'              => $this->time,
                    'updated_at'              => $this->time,
                    'user_id'                 => $transfer['user_id'],
                    'transaction_type_id'     => 3,
                    'transaction_currency_id' => 1,
                    'description'             => Crypt::encrypt($transfer['description']),
                    'completed'               => 1,
                    'date'                    => $transfer['date'],
                    'interest_date'           => $transfer['interest_date'] ?? null,
                    'book_date'               => $transfer['book_date'] ?? null,
                    'process_date'            => $transfer['process_date'] ?? null,
                    'encrypted'               => 1,
                    'order'                   => 0,
                    'tag_count'               => 0,
                ]
            );
            $identifier = 0;
            foreach ($transfer['destination_ids'] as $index => $destination) {
                $description = $transfer['description'] . ' (#' . ($index + 1) . ')';
                $amount      = $transfer['amounts'][$index];
                $source      = $transfer['source_ids'][$index];
                $first       = DB::table('transactions')->insertGetId(
                    [
                        'created_at'             => $this->time,
                        'updated_at'             => $this->time,
                        'account_id'             => $source,
                        'transaction_journal_id' => $journalId,
                        'description'            => $description,
                        'amount'                 => $amount * -1,
                        'identifier'             => $identifier,
                    ]
                );
                $second      = DB::table('transactions')->insertGetId(
                    [
                        'created_at'             => $this->time,
                        'updated_at'             => $this->time,
                        'account_id'             => $destination,
                        'transaction_journal_id' => $journalId,
                        'description'            => $description,
                        'amount'                 => $amount,
                        'identifier'             => $identifier,
                    ]
                );
                $identifier++;
                if (isset($transfer['category_ids'][$index])) {
                    DB::table('category_transaction')->insert(
                        [
                            'category_id'    => $transfer['category_ids'][$index],
                            'transaction_id' => $first,
                        ]
                    );
                    DB::table('category_transaction')->insert(
                        [
                            'category_id'    => $transfer['category_ids'][$index],
                            'transaction_id' => $second,
                        ]
                    );
                }
            }
        }
    }

    /**
     *
     */
    private function createMultiWithdrawals()
    {
        foreach ($this->data['multi-withdrawals'] as $withdrawal) {
            $journalId  = DB::table('transaction_journals')->insertGetId(
                [
                    'created_at'              => $this->time,
                    'updated_at'              => $this->time,
                    'user_id'                 => $withdrawal['user_id'],
                    'transaction_type_id'     => 1,
                    'transaction_currency_id' => 1,
                    'description'             => Crypt::encrypt($withdrawal['description']),
                    'completed'               => 1,
                    'date'                    => $withdrawal['date'],
                    'interest_date'           => $withdrawal['interest_date'] ?? null,
                    'book_date'               => $withdrawal['book_date'] ?? null,
                    'process_date'            => $withdrawal['process_date'] ?? null,
                    'encrypted'               => 1,
                    'order'                   => 0,
                    'tag_count'               => 0,
                ]
            );
            $identifier = 0;
            foreach ($withdrawal['destination_ids'] as $index => $destination) {
                $description = $withdrawal['description'] . ' (#' . ($index + 1) . ')';
                $amount      = $withdrawal['amounts'][$index];
                $first       = DB::table('transactions')->insertGetId(
                    [
                        'created_at'             => $this->time,
                        'updated_at'             => $this->time,
                        'account_id'             => $withdrawal['source_id'],
                        'transaction_journal_id' => $journalId,
                        'description'            => $description,
                        'amount'                 => $amount * -1,
                        'identifier'             => $identifier,
                    ]
                );
                $second      = DB::table('transactions')->insertGetId(
                    [
                        'created_at'             => $this->time,
                        'updated_at'             => $this->time,
                        'account_id'             => $destination,
                        'transaction_journal_id' => $journalId,
                        'description'            => $description,
                        'amount'                 => $amount,
                        'identifier'             => $identifier,
                    ]
                );
                $identifier++;
                // link first and second to budget and category, if present.
                if (isset($withdrawal['budget_ids'][$index])) {
                    DB::table('budget_transaction')->insert(
                        [
                            'budget_id'      => $withdrawal['budget_ids'][$index],
                            'transaction_id' => $first,
                        ]
                    );
                    DB::table('budget_transaction')->insert(
                        [
                            'budget_id'      => $withdrawal['budget_ids'][$index],
                            'transaction_id' => $second,
                        ]
                    );
                }

                if (isset($withdrawal['category_ids'][$index])) {
                    DB::table('category_transaction')->insert(
                        [
                            'category_id'    => $withdrawal['category_ids'][$index],
                            'transaction_id' => $first,
                        ]
                    );
                    DB::table('category_transaction')->insert(
                        [
                            'category_id'    => $withdrawal['category_ids'][$index],
                            'transaction_id' => $second,
                        ]
                    );
                }
            }
        }
    }

    /**
     *
     */
    private function createPiggyBanks()
    {
        foreach ($this->data['piggy-banks'] as $piggyBank) {
            $piggyId = DB::table('piggy_banks')->insertGetId(
                [
                    'created_at'   => $this->time,
                    'updated_at'   => $this->time,
                    'account_id'   => $piggyBank['account_id'],
                    'name'         => Crypt::encrypt($piggyBank['name']),
                    'targetamount' => $piggyBank['targetamount'],
                    'startdate'    => $piggyBank['startdate'],
                    'order'        => $piggyBank['order'],
                    'encrypted'    => 1,
                ]
            );
            if (isset($piggyBank['currentamount'])) {
                DB::table('piggy_bank_repetitions')->insert(
                    [
                        'created_at'    => $this->time,
                        'updated_at'    => $this->time,
                        'piggy_bank_id' => $piggyId,
                        'startdate'     => $piggyBank['startdate'],
                        'currentamount' => $piggyBank['currentamount'],
                    ]
                );
            }
        }

        foreach ($this->data['piggy-events'] as $event) {
            DB::table('piggy_bank_events')->insert(
                [
                    'created_at'    => $this->time,
                    'updated_at'    => $this->time,
                    'piggy_bank_id' => $event['piggy_bank_id'],
                    'date'          => $event['date'],
                    'amount'        => $event['amount'],
                ]
            );
        }
    }

    /**
     *
     */
    private function createRules()
    {
        $insert = [];
        foreach ($this->data['rule-groups'] as $group) {
            $insert[] = [
                'created_at'  => $this->time,
                'updated_at'  => $this->time,
                'user_id'     => $group['user_id'],
                'order'       => $group['order'],
                'title'       => $group['title'],
                'description' => $group['description'],
                'active'      => 1,
            ];
        }
        DB::table('rule_groups')->insert($insert);
        $insert = [];
        foreach ($this->data['rules'] as $rule) {
            $insert[] = [
                'created_at'      => $this->time,
                'updated_at'      => $this->time,
                'user_id'         => $rule['user_id'],
                'rule_group_id'   => $rule['rule_group_id'],
                'order'           => $rule['order'],
                'active'          => $rule['active'],
                'stop_processing' => $rule['stop_processing'],
                'title'           => $rule['title'],
                'description'     => $rule['description'],
            ];
        }
        DB::table('rules')->insert($insert);

        $insert = [];
        foreach ($this->data['rule-triggers'] as $trigger) {
            $insert[] = [
                'created_at'      => $this->time,
                'updated_at'      => $this->time,
                'rule_id'         => $trigger['rule_id'],
                'order'           => $trigger['order'],
                'active'          => $trigger['active'],
                'stop_processing' => $trigger['stop_processing'],
                'trigger_type'    => $trigger['trigger_type'],
                'trigger_value'   => $trigger['trigger_value'],
            ];
        }
        DB::table('rule_triggers')->insert($insert);

        $insert = [];
        foreach ($this->data['rule-actions'] as $action) {
            $insert[] = [
                'created_at'      => $this->time,
                'updated_at'      => $this->time,
                'rule_id'         => $action['rule_id'],
                'order'           => $action['order'],
                'active'          => $action['active'],
                'stop_processing' => $action['stop_processing'],
                'action_type'     => $action['action_type'],
                'action_value'    => $action['action_value'],
            ];
        }
        DB::table('rule_actions')->insert($insert);
    }

    /**
     *
     */
    private function createTags()
    {
        $insert = [];
        foreach ($this->data['tags'] as $tag) {
            $insert[]
                = [
                'created_at' => $this->time,
                'updated_at' => $this->time,
                'user_id'    => $tag['user_id'],
                'tag'        => Crypt::encrypt($tag['tag']),
                'tagMode'    => $tag['tagMode'],
                'date'       => $tag['date'] ?? null,
            ];

        }
        DB::table('tags')->insert($insert);
    }

    /**
     *
     */
    private function createUsers()
    {
        $insert = [];
        foreach ($this->data['users'] as $user) {
            $insert[]
                = [
                'created_at'     => $this->time,
                'updated_at'     => $this->time,
                'email'          => $user['email'],
                'remember_token' => '',
                'password'       => bcrypt($user['password']),
            ];

        }
        DB::table('users')->insert($insert);
        $insert = [];
        foreach ($this->data['roles'] as $role) {
            $insert[]
                = [
                'user_id' => $role['user_id'],
                'role_id' => $role['role'],
            ];
        }
        DB::table('role_user')->insert($insert);
    }

    /**
     *
     */
    private function go()
    {
        $this->createUsers();
        $this->createAccounts();
        $this->createBills();
        $this->createBudgets();
        $this->createCategories();
        $this->createPiggyBanks();
        $this->createRules();
        $this->createTags();
        $this->createJournals();
        $this->createAttachments();
        $this->createMultiWithdrawals();
        $this->createMultiDeposits();
        $this->createMultiTransfers();
        $this->createImportJobs();
        $this->createCurrencies();
        $this->createExportJobs();
    }

}
