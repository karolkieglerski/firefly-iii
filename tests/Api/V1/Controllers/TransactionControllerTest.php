<?php
/**
 * TransactionControllerTest.php
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

namespace Tests\Api\V1\Controllers;


use FireflyIII\Helpers\Collector\JournalCollector;
use FireflyIII\Helpers\Collector\JournalCollectorInterface;
use FireflyIII\Helpers\Filter\NegativeAmountFilter;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use Illuminate\Support\Collection;
use Laravel\Passport\Passport;
use Log;
use Tests\TestCase;

/**
 * todo test fire of rules with parameter
 * Class TransactionControllerTest
 */
class TransactionControllerTest extends TestCase
{
    /**
     *
     */
    public function setUp()
    {
        parent::setUp();
        Passport::actingAs($this->user());
        Log::debug('Now in Api/TransactionControllerTest.');
    }

    /**
     * Destroy account over API.
     *
     * @covers \FireflyIII\Api\V1\Controllers\TransactionController::__construct
     * @covers \FireflyIII\Api\V1\Controllers\TransactionController::delete
     */
    public function testDelete()
    {
        // mock stuff:
        $repository = $this->mock(JournalRepositoryInterface::class);

        // mock calls:
        $repository->shouldReceive('setUser')->once();
        $repository->shouldReceive('delete')->once()->andReturn(true);

        // get account:
        $transaction = $this->user()->transactions()->first();

        // call API
        $response = $this->delete('/api/v1/transactions/' . $transaction->id);
        $response->assertStatus(204);

    }

    /**
     * Submit with bad currency code
     *
     * @covers \FireflyIII\Api\V1\Controllers\TransactionController::store
     * @covers \FireflyIII\Api\V1\Requests\TransactionRequest
     */
    public function testFailCurrencyCode()
    {
        // mock stuff:
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $accountRepos = $this->mock(AccountRepositoryInterface::class);
        $account      = $this->user()->accounts()->where('account_type_id', 3)->first();

        // mock calls:
        $journalRepos->shouldReceive('setUser')->once();
        $accountRepos->shouldReceive('setUser')->once();
        $accountRepos->shouldReceive('getAccountsById')->andReturn(new Collection([$account]));


        $data = [
            'description'  => 'Some transaction #' . rand(1, 1000),
            'date'         => '2018-01-01',
            'type'         => 'withdrawal',
            'transactions' => [
                [
                    'amount'        => '10',
                    'currency_code' => 'FU2',
                    'source_id'     => $account->id,
                ],
            ],
        ];

        // test API
        $response = $this->post('/api/v1/transactions', $data, ['Accept' => 'application/json']);
        $response->assertStatus(422);
        $response->assertExactJson(
            [
                'message' => 'The given data was invalid.',
                'errors'  => [
                    'transactions.0.currency_code' => [
                        'The selected transactions.0.currency_code is invalid.',
                    ],
                ],
            ]
        );
    }

    /**
     * Submit with bad currency ID.
     *
     * @covers \FireflyIII\Api\V1\Controllers\TransactionController::store
     * @covers \FireflyIII\Api\V1\Requests\TransactionRequest
     */
    public function testFailCurrencyId()
    {
        // mock stuff:
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $accountRepos = $this->mock(AccountRepositoryInterface::class);
        $account      = $this->user()->accounts()->where('account_type_id', 3)->first();

        // mock calls:
        $journalRepos->shouldReceive('setUser')->once();
        $accountRepos->shouldReceive('setUser')->once();
        $accountRepos->shouldReceive('getAccountsById')->andReturn(new Collection([$account]));

        $data = [
            'description'  => 'Some transaction #' . rand(1, 1000),
            'date'         => '2018-01-01',
            'type'         => 'withdrawal',
            'transactions' => [
                [
                    'amount'      => '10',
                    'currency_id' => 1991,
                    'source_id'   => $account->id,
                ],
            ],
        ];

        // test API
        $response = $this->post('/api/v1/transactions', $data, ['Accept' => 'application/json']);
        $response->assertStatus(422);
        $response->assertExactJson(
            [
                'message' => 'The given data was invalid.',
                'errors'  => [
                    'transactions.0.currency_id' => [
                        'The selected transactions.0.currency_id is invalid.',
                    ],
                ],
            ]
        );
    }

    /**
     * Empty descriptions
     *
     * @covers \FireflyIII\Api\V1\Controllers\TransactionController::store
     * @covers \FireflyIII\Api\V1\Requests\TransactionRequest
     */
    public function testFailEmptyDescriptions()
    {
        // mock stuff:
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $accountRepos = $this->mock(AccountRepositoryInterface::class);
        $account      = $this->user()->accounts()->where('account_type_id', 3)->first();

        // mock calls:
        $journalRepos->shouldReceive('setUser')->once();
        $accountRepos->shouldReceive('setUser')->once();
        $accountRepos->shouldReceive('getAccountsById')->andReturn(new Collection([$account]));
        $data = [
            'description'  => '',
            'date'         => '2018-01-01',
            'type'         => 'withdrawal',
            'transactions' => [
                [
                    'amount'      => '10',
                    'currency_id' => 1,
                    'source_id'   => $account->id,
                    'description' => '',
                ],


            ],
        ];

        // test API
        $response = $this->post('/api/v1/transactions', $data, ['Accept' => 'application/json']);
        $response->assertStatus(422);
        $response->assertExactJson(
            [
                'message' => 'The given data was invalid.',
                'errors'  => [
                    'description'                => [
                        'The description field is required.',
                        'The description must be between 1 and 255 characters.',
                    ],
                    'transactions.0.description' => [
                        'Transaction description should not equal global description.',
                    ],
                ],
            ]
        );
    }

    /**
     * Submit all empty descriptions for transactions.
     *
     * @covers \FireflyIII\Api\V1\Controllers\TransactionController::store
     * @covers \FireflyIII\Api\V1\Requests\TransactionRequest
     */
    public function testFailEmptySplitDescriptions()
    {
        // mock stuff:
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $accountRepos = $this->mock(AccountRepositoryInterface::class);
        $account      = $this->user()->accounts()->where('account_type_id', 3)->first();

        // mock calls:
        $journalRepos->shouldReceive('setUser')->once();
        $accountRepos->shouldReceive('setUser');
        $accountRepos->shouldReceive('getAccountsById')->andReturn(new Collection([$account]));

        $data = [
            'description'  => 'Split journal #' . rand(1, 1000),
            'date'         => '2018-01-01',
            'type'         => 'withdrawal',
            'transactions' => [
                [
                    'amount'      => '10',
                    'currency_id' => 1,
                    'source_id'   => $account->id,
                    'description' => '',
                ],
                [
                    'amount'      => '10',
                    'currency_id' => 1,
                    'source_id'   => $account->id,
                    'description' => '',
                ],
            ],
        ];

        // test API
        $response = $this->post('/api/v1/transactions', $data, ['Accept' => 'application/json']);
        $response->assertStatus(422);
        $response->assertExactJson(
            [
                'message' => 'The given data was invalid.',
                'errors'  => [
                    'transactions.0.description' => [
                        'The transaction description field is required.',
                    ],
                    'transactions.1.description' => [
                        'The transaction description field is required.',
                    ],

                ],
            ]
        );
    }

    /**
     * Submitted expense account instead of asset account.
     *
     * @covers \FireflyIII\Api\V1\Controllers\TransactionController::store
     * @covers \FireflyIII\Api\V1\Requests\TransactionRequest
     * @covers \FireflyIII\Rules\BelongsUser
     */
    public function testFailExpenseID()
    {
        // mock stuff:
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $accountRepos = $this->mock(AccountRepositoryInterface::class);
        $account      = $this->user()->accounts()->where('account_type_id', 4)->first();

        // mock calls:
        $journalRepos->shouldReceive('setUser')->once();
        $accountRepos->shouldReceive('setUser');
        $accountRepos->shouldReceive('getAccountsById')->andReturn(new Collection([$account]));

        $data = [
            'description'  => 'Some transaction #' . rand(1, 1000),
            'date'         => '2018-01-01',
            'type'         => 'withdrawal',
            'transactions' => [
                [
                    'amount'      => '10',
                    'currency_id' => 1,
                    'source_id'   => $account->id,
                ],


            ],
        ];

        // test API
        $response = $this->post('/api/v1/transactions', $data, ['Accept' => 'application/json']);
        $response->assertStatus(422);
        $response->assertExactJson(
            [
                'message' => 'The given data was invalid.',
                'errors'  => [
                    'transactions.0.source_id' => [
                        'This value is invalid for this field.',
                    ],
                ],
            ]
        );
    }

    /**
     * Submitted expense account name instead of asset account name.
     *
     * @covers \FireflyIII\Api\V1\Controllers\TransactionController::store
     * @covers \FireflyIII\Api\V1\Requests\TransactionRequest
     */
    public function testFailExpenseName()
    {
        // mock stuff:
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $accountRepos = $this->mock(AccountRepositoryInterface::class);

        $journalRepos->shouldReceive('setUser')->once();
        $accountRepos->shouldReceive('setUser');
        $accountRepos->shouldReceive('getAccountsById')->andReturn(new Collection);
        $accountRepos->shouldReceive('findByName')->andReturn(null);

        $data = [
            'description'  => 'Some transaction #' . rand(1, 1000),
            'date'         => '2018-01-01',
            'type'         => 'withdrawal',
            'transactions' => [
                [
                    'amount'      => '10',
                    'currency_id' => 1,
                    'source_name' => 'Expense name',
                ],


            ],
        ];

        // test API
        $response = $this->post('/api/v1/transactions', $data, ['Accept' => 'application/json']);
        $response->assertStatus(422);
        $response->assertExactJson(
            [
                'message' => 'The given data was invalid.',
                'errors'  => [
                    'transactions.0.source_name' => [
                        'This value is invalid for this field.',
                    ],
                ],
            ]
        );
    }

    /**
     * Submit no asset account info at all.
     *
     * @covers \FireflyIII\Api\V1\Controllers\TransactionController::store
     * @covers \FireflyIII\Api\V1\Requests\TransactionRequest
     */
    public function testFailNoAsset()
    {
        // mock stuff:
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $accountRepos = $this->mock(AccountRepositoryInterface::class);

        $journalRepos->shouldReceive('setUser')->once();
        $accountRepos->shouldReceive('setUser');

        $data = [
            'description'  => 'Some transaction #' . rand(1, 1000),
            'date'         => '2018-01-01',
            'type'         => 'withdrawal',
            'transactions' => [
                [
                    'amount'      => '10',
                    'currency_id' => 1,
                ],


            ],
        ];

        // test API
        $response = $this->post('/api/v1/transactions', $data, ['Accept' => 'application/json']);
        $response->assertStatus(422);
        $response->assertExactJson(
            [
                'message' => 'The given data was invalid.',
                'errors'  => [
                    'transactions.0.source_id' => [
                        'The transactions.0.source_id field is required.',
                    ],
                ],
            ]
        );
    }

    /**
     * Submit no transactions.
     *
     * @covers \FireflyIII\Api\V1\Controllers\TransactionController::store
     * @covers \FireflyIII\Api\V1\Requests\TransactionRequest
     */
    public function testFailNoData()
    {
        // mock stuff:
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $accountRepos = $this->mock(AccountRepositoryInterface::class);

        $journalRepos->shouldReceive('setUser')->once();
        $accountRepos->shouldReceive('setUser');

        $data = [
            'description'  => 'Some transaction #' . rand(1, 1000),
            'date'         => '2018-01-01',
            'type'         => 'withdrawal',
            'transactions' => [],
        ];

        // test API
        $response = $this->post('/api/v1/transactions', $data, ['Accept' => 'application/json']);
        $response->assertStatus(422);
        $response->assertExactJson(
            [
                'message' => 'The given data was invalid.',
                'errors'  => [
                    'description' => [
                        'Need at least one transaction.',
                    ],
                ],
            ]
        );
    }

    /**
     * Submit foreign currency without foreign currency info.
     *
     * @covers \FireflyIII\Api\V1\Controllers\TransactionController::store
     * @covers \FireflyIII\Api\V1\Requests\TransactionRequest
     */
    public function testFailNoForeignCurrencyInfo()
    {
        // mock stuff:
        $account      = $this->user()->accounts()->where('account_type_id', 3)->first();
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $accountRepos = $this->mock(AccountRepositoryInterface::class);

        $journalRepos->shouldReceive('setUser')->once();
        $accountRepos->shouldReceive('setUser');
        $accountRepos->shouldReceive('getAccountsById')->andReturn(new Collection([$account]));


        $data = [
            'description'  => 'Split journal #' . rand(1, 1000),
            'date'         => '2018-01-01',
            'type'         => 'withdrawal',
            'transactions' => [
                [
                    'amount'         => '10',
                    'currency_id'    => 1,
                    'foreign_amount' => 10,
                    'source_id'      => $account->id,
                ],
            ],
        ];

        // test API
        $response = $this->post('/api/v1/transactions', $data, ['Accept' => 'application/json']);
        $response->assertStatus(422);
        $response->assertExactJson(
            [
                'message' => 'The given data was invalid.',
                'errors'  => [
                    'transactions.0.foreign_amount' => [
                        'The content of this field is invalid without currency information.',
                    ],

                ],
            ]
        );
    }

    /**
     * Submit revenue ID instead of expense ID.
     *
     * @covers \FireflyIII\Api\V1\Controllers\TransactionController::store
     * @covers \FireflyIII\Api\V1\Requests\TransactionRequest
     */
    public function testFailOpposingRevenueID()
    {
        $account  = $this->user()->accounts()->where('account_type_id', 3)->first();
        $opposing = $this->user()->accounts()->where('account_type_id', 5)->first();

        // mock stuff:
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $accountRepos = $this->mock(AccountRepositoryInterface::class);

        $journalRepos->shouldReceive('setUser')->once();
        $accountRepos->shouldReceive('setUser');
        $accountRepos->shouldReceive('getAccountsById')->andReturn(new Collection([$account]), new Collection([$opposing]));


        $data = [
            'description'  => 'Some transaction #' . rand(1, 1000),
            'date'         => '2018-01-01',
            'type'         => 'withdrawal',
            'transactions' => [
                [
                    'amount'         => '10',
                    'currency_id'    => 1,
                    'source_id'      => $account->id,
                    'destination_id' => $opposing->id,
                ],


            ],
        ];

        // test API
        $response = $this->post('/api/v1/transactions', $data, ['Accept' => 'application/json']);
        $response->assertStatus(422);
        $response->assertExactJson(
            [
                'message' => 'The given data was invalid.',
                'errors'  => [
                    'transactions.0.destination_id' => [
                        'This value is invalid for this field.',
                    ],

                ],
            ]
        );
    }

    /**
     * Submit journal with a bill ID that is not yours.
     *
     * @covers \FireflyIII\Api\V1\Controllers\TransactionController::store
     * @covers \FireflyIII\Api\V1\Requests\TransactionRequest
     * @covers \FireflyiII\Rules\BelongsUser
     */
    public function testFailOwnershipBillId()
    {
        $account      = $this->user()->accounts()->where('account_type_id', 3)->first();
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $accountRepos = $this->mock(AccountRepositoryInterface::class);

        $journalRepos->shouldReceive('setUser')->once();
        $accountRepos->shouldReceive('setUser');
        $accountRepos->shouldReceive('getAccountsById')->andReturn(new Collection([$account]));

        // move account to other user
        $bill          = $this->user()->bills()->first();
        $bill->user_id = $this->emptyUser()->id;
        $bill->save();

        // submit with another account.

        $data = [
            'description'  => 'Some transaction #' . rand(1, 1000),
            'date'         => '2018-01-01',
            'type'         => 'withdrawal',
            'bill_id'      => $bill->id,
            'transactions' => [
                [
                    'amount'      => '10',
                    'currency_id' => 1,
                    'source_id'   => $account->id,
                ],


            ],
        ];

        // test API
        $response = $this->post('/api/v1/transactions', $data, ['Accept' => 'application/json']);
        $response->assertStatus(422);
        $response->assertExactJson(
            [
                'message' => 'The given data was invalid.',
                'errors'  => [
                    'bill_id' => [
                        'This value is invalid for this field.',
                    ],

                ],
            ]
        );
        // put bill back:
        $bill->user_id = $this->user()->id;
        $bill->save();
    }

    /**
     * Submit journal with a bill ID that is not yours.
     *
     * @covers \FireflyIII\Api\V1\Controllers\TransactionController::store
     * @covers \FireflyIII\Api\V1\Requests\TransactionRequest
     * @covers \FireflyiII\Rules\BelongsUser
     */
    public function testFailOwnershipBillName()
    {
        $account      = $this->user()->accounts()->where('account_type_id', 3)->first();
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $accountRepos = $this->mock(AccountRepositoryInterface::class);

        $journalRepos->shouldReceive('setUser')->once();
        $accountRepos->shouldReceive('setUser');
        $accountRepos->shouldReceive('getAccountsById')->andReturn(new Collection([$account]));

        // move account to other user
        $bill          = $this->user()->bills()->first();
        $bill->user_id = $this->emptyUser()->id;
        $bill->save();

        // submit with another account.
        $data = [
            'description'  => 'Some transaction #' . rand(1, 1000),
            'date'         => '2018-01-01',
            'type'         => 'withdrawal',
            'bill_name'    => $bill->name,
            'transactions' => [
                [
                    'amount'      => '10',
                    'currency_id' => 1,
                    'source_id'   => $account->id,
                ],


            ],
        ];

        // test API
        $response = $this->post('/api/v1/transactions', $data, ['Accept' => 'application/json']);
        $response->assertStatus(422);
        $response->assertExactJson(
            [
                'message' => 'The given data was invalid.',
                'errors'  => [
                    'bill_name' => [
                        'This value is invalid for this field.',
                    ],

                ],
            ]
        );
        // put bill back:
        $bill->user_id = $this->user()->id;
        $bill->save();
    }

    /**
     * Submit journal with a budget ID that is not yours.
     *
     * @covers \FireflyIII\Api\V1\Controllers\TransactionController::store
     * @covers \FireflyIII\Api\V1\Requests\TransactionRequest
     * @covers \FireflyiII\Rules\BelongsUser
     */
    public function testFailOwnershipBudgetId()
    {
        $account      = $this->user()->accounts()->where('account_type_id', 3)->first();
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $accountRepos = $this->mock(AccountRepositoryInterface::class);

        $journalRepos->shouldReceive('setUser')->once();
        $accountRepos->shouldReceive('setUser');
        $accountRepos->shouldReceive('getAccountsById')->andReturn(new Collection([$account]));

        // move account to other user
        $budget          = $this->user()->budgets()->first();
        $budget->user_id = $this->emptyUser()->id;
        $budget->save();

        // submit with another account.
        $data = [
            'description'  => 'Some transaction #' . rand(1, 1000),
            'date'         => '2018-01-01',
            'type'         => 'withdrawal',
            'transactions' => [
                [
                    'amount'      => '10',
                    'currency_id' => 1,
                    'source_id'   => $account->id,
                    'budget_id'   => $budget->id,
                ],


            ],
        ];

        // test API
        $response = $this->post('/api/v1/transactions', $data, ['Accept' => 'application/json']);
        $response->assertStatus(422);
        $response->assertExactJson(
            [
                'message' => 'The given data was invalid.',
                'errors'  => [
                    'transactions.0.budget_id' => [
                        'This value is invalid for this field.',
                    ],

                ],
            ]
        );
        // put budget back:
        $budget->user_id = $this->user()->id;
        $budget->save();
    }

    /**
     * Submit journal with a budget name that is not yours.
     *
     * @covers \FireflyIII\Api\V1\Controllers\TransactionController::store
     * @covers \FireflyIII\Api\V1\Requests\TransactionRequest
     * @covers \FireflyiII\Rules\BelongsUser
     */
    public function testFailOwnershipBudgetName()
    {
        $account      = $this->user()->accounts()->where('account_type_id', 3)->first();
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $accountRepos = $this->mock(AccountRepositoryInterface::class);

        $journalRepos->shouldReceive('setUser')->once();
        $accountRepos->shouldReceive('setUser');
        $accountRepos->shouldReceive('getAccountsById')->andReturn(new Collection([$account]));

        // move account to other user
        $budget          = $this->user()->budgets()->first();
        $budget->user_id = $this->emptyUser()->id;
        $budget->save();

        // submit with another account.
        $data = [
            'description'  => 'Some transaction #' . rand(1, 1000),
            'date'         => '2018-01-01',
            'type'         => 'withdrawal',
            'transactions' => [
                [
                    'amount'      => '10',
                    'currency_id' => 1,
                    'source_id'   => $account->id,
                    'budget_name' => $budget->name,
                ],


            ],
        ];

        // test API
        $response = $this->post('/api/v1/transactions', $data, ['Accept' => 'application/json']);
        $response->assertStatus(422);
        $response->assertExactJson(
            [
                'message' => 'The given data was invalid.',
                'errors'  => [
                    'transactions.0.budget_name' => [
                        'This value is invalid for this field.',
                    ],

                ],
            ]
        );
        // put bill back:
        $budget->user_id = $this->user()->id;
        $budget->save();
    }

    /**
     * Submit journal with a category ID that is not yours.
     *
     * @covers \FireflyIII\Api\V1\Controllers\TransactionController::store
     * @covers \FireflyIII\Api\V1\Requests\TransactionRequest
     * @covers \FireflyiII\Rules\BelongsUser
     */
    public function testFailOwnershipCategoryId()
    {
        $account      = $this->user()->accounts()->where('account_type_id', 3)->first();
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $accountRepos = $this->mock(AccountRepositoryInterface::class);

        $journalRepos->shouldReceive('setUser')->once();
        $accountRepos->shouldReceive('setUser');
        $accountRepos->shouldReceive('getAccountsById')->andReturn(new Collection([$account]));

        // move account to other user
        $category          = $this->user()->categories()->first();
        $category->user_id = $this->emptyUser()->id;
        $category->save();

        // submit with another account.
        $data = [
            'description'  => 'Some transaction #' . rand(1, 1000),
            'date'         => '2018-01-01',
            'type'         => 'withdrawal',
            'transactions' => [
                [
                    'amount'      => '10',
                    'currency_id' => 1,
                    'source_id'   => $account->id,
                    'category_id' => $category->id,
                ],


            ],
        ];

        // test API
        $response = $this->post('/api/v1/transactions', $data, ['Accept' => 'application/json']);
        $response->assertStatus(422);
        $response->assertExactJson(
            [
                'message' => 'The given data was invalid.',
                'errors'  => [
                    'transactions.0.category_id' => [
                        'This value is invalid for this field.',
                    ],

                ],
            ]
        );
        // put category back:
        $category->user_id = $this->user()->id;
        $category->save();
    }

    /**
     * Submit journal with a piggy bank that is not yours.
     *
     * @covers \FireflyIII\Api\V1\Controllers\TransactionController::store
     * @covers \FireflyIII\Api\V1\Requests\TransactionRequest
     * @covers \FireflyiII\Rules\BelongsUser
     */
    public function testFailOwnershipPiggyBankID()
    {
        // move account to other user
        $move                  = $this->user()->accounts()->where('account_type_id', 3)->first();
        $move->user_id         = $this->emptyUser()->id;
        $piggyBank             = $this->user()->piggyBanks()->first();
        $oldId                 = $piggyBank->account_id;
        $piggyBank->account_id = $move->id;
        $move->save();
        $piggyBank->save();


        $account      = $this->user()->accounts()->where('account_type_id', 3)->first();
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $accountRepos = $this->mock(AccountRepositoryInterface::class);

        $journalRepos->shouldReceive('setUser')->once();
        $accountRepos->shouldReceive('setUser');
        $accountRepos->shouldReceive('getAccountsById')->andReturn(new Collection([$account]));


        // submit with another account.
        $data = [
            'description'   => 'Some transaction #' . rand(1, 1000),
            'date'          => '2018-01-01',
            'type'          => 'withdrawal',
            'piggy_bank_id' => $piggyBank->id,
            'transactions'  => [
                [
                    'amount'      => '10',
                    'currency_id' => 1,
                    'source_id'   => $account->id,
                ],


            ],
        ];

        // test API
        $response = $this->post('/api/v1/transactions', $data, ['Accept' => 'application/json']);
        $response->assertStatus(422);
        $response->assertExactJson(
            [
                'message' => 'The given data was invalid.',
                'errors'  => [
                    'piggy_bank_id' => [
                        'This value is invalid for this field.',
                    ],

                ],
            ]
        );
        // put account back:
        $move->user_id = $this->user()->id;
        $move->save();
        $piggyBank->account_id = $oldId;
        $piggyBank->save();
    }

    /**
     * Submit journal with a piggy bank that is not yours.
     *
     * @covers \FireflyIII\Api\V1\Controllers\TransactionController::store
     * @covers \FireflyIII\Api\V1\Requests\TransactionRequest
     * @covers \FireflyiII\Rules\BelongsUser
     */
    public function testFailOwnershipPiggyBankName()
    {
        // move account to other user
        $move                  = $this->user()->accounts()->where('account_type_id', 3)->first();
        $move->user_id         = $this->emptyUser()->id;
        $piggyBank             = $this->user()->piggyBanks()->first();
        $oldId                 = $piggyBank->account_id;
        $piggyBank->account_id = $move->id;
        $move->save();
        $piggyBank->save();


        $account      = $this->user()->accounts()->where('account_type_id', 3)->first();
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $accountRepos = $this->mock(AccountRepositoryInterface::class);

        $journalRepos->shouldReceive('setUser')->once();
        $accountRepos->shouldReceive('setUser');
        $accountRepos->shouldReceive('getAccountsById')->andReturn(new Collection([$account]));


        // submit with another account.
        $data = [
            'description'     => 'Some transaction #' . rand(1, 1000),
            'date'            => '2018-01-01',
            'type'            => 'withdrawal',
            'piggy_bank_name' => $piggyBank->name,
            'transactions'    => [
                [
                    'amount'      => '10',
                    'currency_id' => 1,
                    'source_id'   => $account->id,
                ],


            ],
        ];

        // test API
        $response = $this->post('/api/v1/transactions', $data, ['Accept' => 'application/json']);
        $response->assertStatus(422);
        $response->assertExactJson(
            [
                'message' => 'The given data was invalid.',
                'errors'  => [
                    'piggy_bank_name' => [
                        'This value is invalid for this field.',
                    ],

                ],
            ]
        );
        // put account back:
        $move->user_id = $this->user()->id;
        $move->save();
        $piggyBank->account_id = $oldId;
        $piggyBank->save();
    }

    /**
     * Submitted revenue account instead of asset account in deposit.
     *
     * @covers \FireflyIII\Api\V1\Controllers\TransactionController::store
     * @covers \FireflyIII\Api\V1\Requests\TransactionRequest
     * @covers \FireflyIII\Rules\BelongsUser
     */
    public function testFailRevenueID()
    {
        $account      = $this->user()->accounts()->where('account_type_id', 4)->first();
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $accountRepos = $this->mock(AccountRepositoryInterface::class);

        $journalRepos->shouldReceive('setUser')->once();
        $accountRepos->shouldReceive('setUser');
        $accountRepos->shouldReceive('getAccountsById')->andReturn(new Collection([$account]));

        $data = [
            'description'  => 'Some transaction #' . rand(1, 1000),
            'date'         => '2018-01-01',
            'type'         => 'deposit',
            'transactions' => [
                [
                    'amount'         => '10',
                    'currency_id'    => 1,
                    'destination_id' => $account->id,
                ],


            ],
        ];

        // test API
        $response = $this->post('/api/v1/transactions', $data, ['Accept' => 'application/json']);
        $response->assertStatus(422);
        $response->assertExactJson(
            [
                'message' => 'The given data was invalid.',
                'errors'  => [
                    'transactions.0.destination_id' => [
                        'This value is invalid for this field.',
                    ],
                ],
            ]
        );
    }

    /**
     * Try to store a withdrawal with different source accounts.
     *
     * @covers \FireflyIII\Api\V1\Controllers\TransactionController::store
     * @covers \FireflyIII\Api\V1\Requests\TransactionRequest
     */
    public function testFailSplitDeposit()
    {
        $account = $this->user()->accounts()->where('account_type_id', 3)->first();
        $second  = $this->user()->accounts()->where('account_type_id', 3)->where('id', '!=', $account->id)->first();

        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $accountRepos = $this->mock(AccountRepositoryInterface::class);

        $journalRepos->shouldReceive('setUser')->once();
        $accountRepos->shouldReceive('setUser');
        $accountRepos->shouldReceive('getAccountsById')->andReturn(new Collection([$account]), new Collection([$second]));


        $data = [
            'description'  => 'Some deposit #' . rand(1, 1000),
            'date'         => '2018-01-01',
            'type'         => 'deposit',
            'transactions' => [
                [
                    'amount'         => '10',
                    'currency_id'    => 1,
                    'destination_id' => $account->id,
                    'description'    => 'Part 1',
                ],
                [
                    'amount'         => '10',
                    'currency_id'    => 1,
                    'destination_id' => $second->id,
                    'description'    => 'Part 2',
                ],


            ],
        ];

        // test API
        $response = $this->post('/api/v1/transactions', $data, ['Accept' => 'application/json']);
        $response->assertStatus(422);
        $response->assertExactJson(
            [
                'message' => 'The given data was invalid.',
                'errors'  => [
                    'transactions.0.destination_id' => [
                        'All accounts in this field must be equal.',
                    ],
                ],
            ]
        );

    }

    /**
     * Try to store a withdrawal with different source accounts.
     *
     * @covers \FireflyIII\Api\V1\Controllers\TransactionController::store
     * @covers \FireflyIII\Api\V1\Requests\TransactionRequest
     */
    public function testFailSplitTransfer()
    {
        $account = $this->user()->accounts()->where('account_type_id', 3)->first();
        $second  = $this->user()->accounts()->where('account_type_id', 3)->where('id', '!=', $account->id)->first();

        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $accountRepos = $this->mock(AccountRepositoryInterface::class);

        $journalRepos->shouldReceive('setUser')->once();
        $accountRepos->shouldReceive('setUser');
        $accountRepos->shouldReceive('getAccountsById')->andReturn(new Collection([$account]), new Collection([$second]));

        $data = [
            'description'  => 'Some transfer #' . rand(1, 1000),
            'date'         => '2018-01-01',
            'type'         => 'transfer',
            'transactions' => [
                [
                    'amount'         => '10',
                    'currency_id'    => 1,
                    'source_id'      => $account->id,
                    'destination_id' => $second->id,
                    'description'    => 'Part 1',
                ],
                [
                    'amount'         => '10',
                    'currency_id'    => 1,
                    'source_id'      => $second->id,
                    'destination_id' => $account->id,
                    'description'    => 'Part 2',
                ],


            ],
        ];

        // test API
        $response = $this->post('/api/v1/transactions', $data, ['Accept' => 'application/json']);
        $response->assertStatus(422);
        $response->assertExactJson(
            [
                'message' => 'The given data was invalid.',
                'errors'  => [
                    'transactions.0.source_id'      => [
                        'All accounts in this field must be equal.',
                    ],
                    'transactions.0.destination_id' => [
                        'All accounts in this field must be equal.',
                    ],
                ],
            ]
        );

    }

    /**
     * Try to store a withdrawal with different source accounts.
     *
     * @covers \FireflyIII\Api\V1\Controllers\TransactionController::store
     * @covers \FireflyIII\Api\V1\Requests\TransactionRequest
     */
    public function testFailSplitWithdrawal()
    {
        $account = $this->user()->accounts()->where('account_type_id', 3)->first();
        $second  = $this->user()->accounts()->where('account_type_id', 3)->where('id', '!=', $account->id)->first();

        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $accountRepos = $this->mock(AccountRepositoryInterface::class);

        $journalRepos->shouldReceive('setUser')->once();
        $accountRepos->shouldReceive('setUser');
        $accountRepos->shouldReceive('getAccountsById')->andReturn(new Collection([$account]), new Collection([$second]));

        $data = [
            'description'  => 'Some transaction #' . rand(1, 1000),
            'date'         => '2018-01-01',
            'type'         => 'withdrawal',
            'transactions' => [
                [
                    'amount'      => '10',
                    'currency_id' => 1,
                    'source_id'   => $account->id,
                    'description' => 'Part 1',
                ],
                [
                    'amount'      => '10',
                    'currency_id' => 1,
                    'source_id'   => $second->id,
                    'description' => 'Part 2',
                ],


            ],
        ];

        // test API
        $response = $this->post('/api/v1/transactions', $data, ['Accept' => 'application/json']);
        $response->assertStatus(422);
        $response->assertExactJson(
            [
                'message' => 'The given data was invalid.',
                'errors'  => [
                    'transactions.0.source_id' => [
                        'All accounts in this field must be equal.',
                    ],
                ],
            ]
        );

    }

    /**
     * Show index.
     *
     * @covers \FireflyIII\Api\V1\Controllers\TransactionController::__construct
     * @covers \FireflyIII\Api\V1\Controllers\TransactionController::index
     * @covers \FireflyIII\Api\V1\Controllers\TransactionController::mapTypes
     *
     * throws \FireflyIII\Exceptions\FireflyException
     */
    public function testIndex()
    {
        $accountRepos = $this->mock(AccountRepositoryInterface::class);
        $accountRepos->shouldReceive('setUser');
        $accountRepos->shouldReceive('getAccountsByType')
                     ->andReturn($this->user()->accounts()->where('account_type_id', 3)->get());

        // get some transactions using the collector:
        $collector = new JournalCollector;
        $collector->setUser($this->user());
        $collector->withOpposingAccount()->withCategoryInformation()->withBudgetInformation();
        $collector->setAllAssetAccounts();
        $collector->setLimit(5)->setPage(1);
        $paginator = $collector->getPaginatedJournals();

        // mock stuff:
        $repository = $this->mock(JournalRepositoryInterface::class);
        $collector  = $this->mock(JournalCollectorInterface::class);
        $repository->shouldReceive('setUser');


        $collector->shouldReceive('setUser')->andReturnSelf();
        $collector->shouldReceive('withOpposingAccount')->andReturnSelf();
        $collector->shouldReceive('withCategoryInformation')->andReturnSelf();
        $collector->shouldReceive('withBudgetInformation')->andReturnSelf();
        $collector->shouldReceive('setAllAssetAccounts')->andReturnSelf();
        $collector->shouldReceive('removeFilter')->andReturnSelf();
        $collector->shouldReceive('setLimit')->andReturnSelf();
        $collector->shouldReceive('setPage')->andReturnSelf();
        $collector->shouldReceive('setTypes')->andReturnSelf();
        $collector->shouldReceive('getPaginatedJournals')->andReturn($paginator);


        // mock some calls:

        // test API
        $response = $this->get('/api/v1/transactions');
        $response->assertStatus(200);
        $response->assertJson(['data' => [],]);
        $response->assertJson(['meta' => ['pagination' => ['total' => true, 'count' => true, 'per_page' => 5, 'current_page' => 1, 'total_pages' => true]],]);
        $response->assertJson(['links' => ['self' => true, 'first' => true, 'last' => true,],]);
        $response->assertHeader('Content-Type', 'application/vnd.api+json');
    }

    /**
     * @covers \FireflyIII\Api\V1\Controllers\TransactionController::__construct
     * @covers \FireflyIII\Api\V1\Controllers\TransactionController::index
     * @covers \FireflyIII\Api\V1\Controllers\TransactionController::mapTypes
     * throws \FireflyIII\Exceptions\FireflyException
     */
    public function testIndexWithRange()
    {
        $accountRepos = $this->mock(AccountRepositoryInterface::class);
        $accountRepos->shouldReceive('setUser');
        $accountRepos->shouldReceive('getAccountsByType')
                     ->andReturn($this->user()->accounts()->where('account_type_id', 3)->get());

        // get some transactions using the collector:
        $collector = new JournalCollector;
        $collector->setUser($this->user());
        $collector->withOpposingAccount()->withCategoryInformation()->withBudgetInformation();
        $collector->setAllAssetAccounts();
        $collector->setLimit(5)->setPage(1);
        $paginator = $collector->getPaginatedJournals();

        // mock stuff:
        $repository = $this->mock(JournalRepositoryInterface::class);
        $collector  = $this->mock(JournalCollectorInterface::class);
        $repository->shouldReceive('setUser');

        $collector->shouldReceive('setUser')->andReturnSelf();
        $collector->shouldReceive('withOpposingAccount')->andReturnSelf();
        $collector->shouldReceive('withCategoryInformation')->andReturnSelf();
        $collector->shouldReceive('withBudgetInformation')->andReturnSelf();
        $collector->shouldReceive('setAllAssetAccounts')->andReturnSelf();
        $collector->shouldReceive('removeFilter')->andReturnSelf();
        $collector->shouldReceive('setLimit')->andReturnSelf();
        $collector->shouldReceive('setPage')->andReturnSelf();
        $collector->shouldReceive('setTypes')->andReturnSelf();
        $collector->shouldReceive('setRange')->andReturnSelf();
        $collector->shouldReceive('getPaginatedJournals')->andReturn($paginator);


        // mock some calls:

        // test API
        $response = $this->get('/api/v1/transactions?start=2018-01-01&end=2018-01-31');
        $response->assertStatus(200);
        $response->assertJson(['data' => [],]);
        $response->assertJson(
            ['meta' =>
                 ['pagination' =>
                      [
                          'total'        => true,
                          'count'        => true,
                          'per_page'     => 5,
                          'current_page' => 1,
                          'total_pages'  => true,
                      ],
                 ],
            ]
        );


        $response->assertJson(['links' => ['self' => true, 'first' => true, 'last' => true,],]);
        $response->assertHeader('Content-Type', 'application/vnd.api+json');
    }

    /**
     * @covers \FireflyIII\Api\V1\Controllers\TransactionController::show
     */
    public function testShowDeposit()
    {
        /** @var TransactionJournal $journal */
        $journal     = $this->user()->transactionJournals()->where('transaction_type_id', 2)->first();
        $transaction = $journal->transactions()->first();

        $accountRepos = $this->mock(AccountRepositoryInterface::class);
        $accountRepos->shouldReceive('setUser');
        $accountRepos->shouldReceive('getAccountsByType')
                     ->andReturn($this->user()->accounts()->where('account_type_id', 3)->get());


        // get some transactions using the collector:
        $collector = new JournalCollector;
        $collector->setUser($this->user());
        $collector->withOpposingAccount()->withCategoryInformation()->withBudgetInformation();
        $collector->setAllAssetAccounts();
        $collector->setJournals(new Collection([$journal]));
        $collector->setLimit(5)->setPage(1);
        $transactions = $collector->getJournals();

        // mock stuff:
        $repository = $this->mock(JournalRepositoryInterface::class);
        $collector  = $this->mock(JournalCollectorInterface::class);
        $repository->shouldReceive('setUser');

        $collector->shouldReceive('setUser')->andReturnSelf();
        $collector->shouldReceive('withOpposingAccount')->andReturnSelf();
        $collector->shouldReceive('withCategoryInformation')->andReturnSelf()->once();
        $collector->shouldReceive('withBudgetInformation')->andReturnSelf()->once();
        $collector->shouldReceive('setJournals')->andReturnSelf()->once();
        $collector->shouldReceive('addFilter')->withArgs([NegativeAmountFilter::class])->andReturnSelf()->once();
        $collector->shouldReceive('getJournals')->andReturn($transactions);

        // test API
        $response = $this->get('/api/v1/transactions/' . $transaction->id);
        $response->assertStatus(200);
        $response->assertJson(
            [
                'data' => [
                    'attributes' => [
                        'description' => $journal->description,
                        'type'        => 'Deposit',
                    ],
                    'links'      => [
                        0      => [],
                        'self' => true,
                    ],
                ],

            ]
        );

        $response->assertHeader('Content-Type', 'application/vnd.api+json');

    }

    /**
     * @covers \FireflyIII\Api\V1\Controllers\TransactionController::show
     */
    public function testShowWithdrawal()
    {
        /** @var TransactionJournal $journal */
        $journal     = $this->user()->transactionJournals()->where('transaction_type_id', 1)->first();
        $transaction = $journal->transactions()->first();

        $accountRepos = $this->mock(AccountRepositoryInterface::class);
        $accountRepos->shouldReceive('setUser');
        $accountRepos->shouldReceive('getAccountsByType')
                     ->andReturn($this->user()->accounts()->where('account_type_id', 3)->get());


        // get some transactions using the collector:
        $collector = new JournalCollector;
        $collector->setUser($this->user());
        $collector->withOpposingAccount()->withCategoryInformation()->withBudgetInformation();
        $collector->setAllAssetAccounts();
        $collector->setJournals(new Collection([$journal]));
        $collector->setLimit(5)->setPage(1);
        $transactions = $collector->getJournals();

        // mock stuff:
        $repository = $this->mock(JournalRepositoryInterface::class);
        $collector  = $this->mock(JournalCollectorInterface::class);
        $repository->shouldReceive('setUser');

        $collector->shouldReceive('setUser')->andReturnSelf();
        $collector->shouldReceive('withOpposingAccount')->andReturnSelf();
        $collector->shouldReceive('withCategoryInformation')->andReturnSelf()->once();
        $collector->shouldReceive('withBudgetInformation')->andReturnSelf()->once();
        $collector->shouldReceive('setJournals')->andReturnSelf()->once();
        $collector->shouldReceive('addFilter')->andReturnSelf()->once();
        $collector->shouldReceive('getJournals')->andReturn($transactions);

        // test API
        $response = $this->get('/api/v1/transactions/' . $transaction->id);
        $response->assertStatus(200);
        $response->assertJson(
            [
                'data' => [
                    'attributes' => [
                        'description' => $journal->description,
                    ],
                    'links'      => [
                        0      => [],
                        'self' => true,
                    ],
                ],

            ]
        );

        $response->assertHeader('Content-Type', 'application/vnd.api+json');

    }

    /**
     * Submit a transaction (withdrawal) with attached bill ID
     *
     * @covers \FireflyIII\Api\V1\Controllers\TransactionController::store
     * @covers \FireflyIII\Api\V1\Requests\TransactionRequest
     */
    public function testSuccessBillId()
    {
        // default journal:
        $journal      = $this->user()->transactionJournals()->first();
        $account      = $this->user()->accounts()->where('account_type_id', 3)->first();
        $journalRepos = $this->mock(JournalRepositoryInterface::class)->makePartial();
        $accountRepos = $this->mock(AccountRepositoryInterface::class);

        $journalRepos->shouldReceive('setUser')->once();
        $accountRepos->shouldReceive('setUser');
        $accountRepos->shouldReceive('getAccountsById')->andReturn(new Collection([$account]));
        $journalRepos->shouldReceive('store')->andReturn($journal)->once();

        $bill = $this->user()->bills()->first();
        $data = [
            'description'  => 'Some transaction #' . rand(1, 1000),
            'date'         => '2018-01-01',
            'type'         => 'withdrawal',
            'bill_id'      => $bill->id,
            'transactions' => [
                [
                    'amount'      => '10',
                    'currency_id' => 1,
                    'source_id'   => $account->id,
                ],


            ],
        ];

        // test API
        $response = $this->post('/api/v1/transactions', $data, ['Accept' => 'application/json']);
        $response->assertStatus(200);
    }

    /**
     * Submit a transaction (withdrawal) with attached bill ID
     *
     * @covers \FireflyIII\Api\V1\Controllers\TransactionController::store
     * @covers \FireflyIII\Api\V1\Requests\TransactionRequest
     */
    public function testSuccessBillName()
    {
        // default journal:
        $journal      = $this->user()->transactionJournals()->first();
        $account      = $this->user()->accounts()->where('account_type_id', 3)->first();
        $journalRepos = $this->mock(JournalRepositoryInterface::class)->makePartial();
        $accountRepos = $this->mock(AccountRepositoryInterface::class);

        $journalRepos->shouldReceive('setUser')->once();
        $accountRepos->shouldReceive('setUser');
        $accountRepos->shouldReceive('getAccountsById')->andReturn(new Collection([$account]));
        $journalRepos->shouldReceive('store')->andReturn($journal)->once();

        $bill = $this->user()->bills()->first();
        $data = [
            'description'  => 'Some transaction #' . rand(1, 1000),
            'date'         => '2018-01-01',
            'type'         => 'withdrawal',
            'bill_name'    => $bill->name,
            'transactions' => [
                [
                    'amount'      => '10',
                    'currency_id' => 1,
                    'source_id'   => $account->id,
                ],


            ],
        ];

        // test API
        $response = $this->post('/api/v1/transactions', $data, ['Accept' => 'application/json']);
        $response->assertStatus(200);
    }

    /**
     * Submit the minimum amount of data required to create a withdrawal.
     *
     * @covers \FireflyIII\Api\V1\Controllers\TransactionController::store
     * @covers \FireflyIII\Api\V1\Requests\TransactionRequest
     */
    public function testSuccessStoreAccountName()
    {
        // default journal:
        $journal      = $this->user()->transactionJournals()->first();
        $account      = $this->user()->accounts()->where('account_type_id', 3)->first();
        $journalRepos = $this->mock(JournalRepositoryInterface::class)->makePartial();
        $accountRepos = $this->mock(AccountRepositoryInterface::class);

        $journalRepos->shouldReceive('setUser')->once();
        $accountRepos->shouldReceive('setUser');
        $accountRepos->shouldReceive('getAccountsById')->andReturn(new Collection([$account]));
        $journalRepos->shouldReceive('store')->andReturn($journal)->once();

        $data = [
            'description'  => 'Some transaction #' . rand(1, 1000),
            'date'         => '2018-01-01',
            'type'         => 'withdrawal',
            'transactions' => [
                [
                    'amount'      => '10',
                    'currency_id' => 1,
                    'source_name' => $account->name,
                ],


            ],
        ];

        // test API
        $response = $this->post('/api/v1/transactions', $data, ['Accept' => 'application/json']);
        $response->assertStatus(200);
    }

    /**
     * Submit the minimum amount of data required to create a withdrawal.
     *
     * @covers \FireflyIII\Api\V1\Controllers\TransactionController::store
     * @covers \FireflyIII\Api\V1\Requests\TransactionRequest
     */
    public function testSuccessStoreBasic()
    {
        // default journal:
        $journal      = $this->user()->transactionJournals()->first();
        $account      = $this->user()->accounts()->where('account_type_id', 3)->first();
        $journalRepos = $this->mock(JournalRepositoryInterface::class)->makePartial();
        $accountRepos = $this->mock(AccountRepositoryInterface::class);

        $journalRepos->shouldReceive('setUser')->once();
        $accountRepos->shouldReceive('setUser');
        $accountRepos->shouldReceive('getAccountsById')->andReturn(new Collection([$account]));
        $journalRepos->shouldReceive('store')->andReturn($journal)->once();

        $data = [
            'description'  => 'Some transaction #' . rand(1, 1000),
            'date'         => '2018-01-01',
            'type'         => 'withdrawal',
            'transactions' => [
                [
                    'amount'      => '10',
                    'currency_id' => 1,
                    'source_id'   => $account->id,
                ],


            ],
        ];

        // test API
        $response = $this->post('/api/v1/transactions', $data, ['Accept' => 'application/json']);
        $response->assertStatus(200);
    }

    /**
     * Submit the minimum amount of data required to create a deposit.
     *
     * @covers \FireflyIII\Api\V1\Controllers\TransactionController::store
     * @covers \FireflyIII\Api\V1\Requests\TransactionRequest
     */
    public function testSuccessStoreBasicDeposit()
    {
        // default journal:
        $journal      = $this->user()->transactionJournals()->first();
        $account      = $this->user()->accounts()->where('account_type_id', 3)->first();
        $journalRepos = $this->mock(JournalRepositoryInterface::class)->makePartial();
        $accountRepos = $this->mock(AccountRepositoryInterface::class);

        $journalRepos->shouldReceive('setUser')->once();
        $accountRepos->shouldReceive('setUser');
        $accountRepos->shouldReceive('getAccountsById')->andReturn(new Collection([$account]));
        $journalRepos->shouldReceive('store')->andReturn($journal)->once();

        $data = [
            'description'  => 'Some transaction #' . rand(1, 1000),
            'date'         => '2018-01-01',
            'type'         => 'deposit',
            'transactions' => [
                [
                    'amount'         => '10',
                    'currency_id'    => 1,
                    'destination_id' => $account->id,
                ],


            ],
        ];

        // test API
        $response = $this->post('/api/v1/transactions', $data, ['Accept' => 'application/json']);
        $response->assertStatus(200);
    }

    /**
     * Submit with existing budget ID, see it reflected in output.
     *
     * @covers \FireflyIII\Api\V1\Controllers\TransactionController::store
     * @covers \FireflyIII\Api\V1\Requests\TransactionRequest
     */
    public function testSuccessStoreBudgetId()
    {
        $budget       = $this->user()->budgets()->first();
        $journal      = $this->user()->transactionJournals()->first();
        $account      = $this->user()->accounts()->where('account_type_id', 3)->first();
        $journalRepos = $this->mock(JournalRepositoryInterface::class)->makePartial();
        $accountRepos = $this->mock(AccountRepositoryInterface::class);

        $journalRepos->shouldReceive('setUser')->once();
        $accountRepos->shouldReceive('setUser');
        $accountRepos->shouldReceive('getAccountsById')->andReturn(new Collection([$account]));
        $journalRepos->shouldReceive('store')->andReturn($journal)->once();
        $data = [
            'description'  => 'Some transaction #' . rand(1, 1000),
            'date'         => '2018-01-01',
            'type'         => 'withdrawal',
            'transactions' => [
                [
                    'amount'      => '10',
                    'currency_id' => 1,
                    'source_id'   => $account->id,
                    'budget_id'   => $budget->id,
                ],


            ],
        ];

        // test API
        $response = $this->post('/api/v1/transactions', $data, ['Accept' => 'application/json']);
        $response->assertStatus(200);
    }

    /**
     * Submit with existing budget name, see it reflected in output.
     *
     * @covers \FireflyIII\Api\V1\Controllers\TransactionController::store
     * @covers \FireflyIII\Api\V1\Requests\TransactionRequest
     */
    public function testSuccessStoreBudgetName()
    {
        $budget       = $this->user()->budgets()->first();
        $journal      = $this->user()->transactionJournals()->first();
        $account      = $this->user()->accounts()->where('account_type_id', 3)->first();
        $journalRepos = $this->mock(JournalRepositoryInterface::class)->makePartial();
        $accountRepos = $this->mock(AccountRepositoryInterface::class);

        $journalRepos->shouldReceive('setUser')->once();
        $accountRepos->shouldReceive('setUser');
        $accountRepos->shouldReceive('getAccountsById')->andReturn(new Collection([$account]));
        $journalRepos->shouldReceive('store')->andReturn($journal)->once();

        $data = [
            'description'  => 'Some transaction #' . rand(1, 1000),
            'date'         => '2018-01-01',
            'type'         => 'withdrawal',
            'transactions' => [
                [
                    'amount'      => '10',
                    'currency_id' => 1,
                    'source_id'   => $account->id,
                    'budget_name' => $budget->name,
                ],


            ],
        ];

        // test API
        $response = $this->post('/api/v1/transactions', $data, ['Accept' => 'application/json']);
        $response->assertStatus(200);
    }

    /**
     * Submit with existing category ID, see it reflected in output.
     *
     * @covers \FireflyIII\Api\V1\Controllers\TransactionController::store
     * @covers \FireflyIII\Api\V1\Requests\TransactionRequest
     */
    public function testSuccessStoreCategoryID()
    {
        $category     = $this->user()->categories()->first();
        $journal      = $this->user()->transactionJournals()->first();
        $account      = $this->user()->accounts()->where('account_type_id', 3)->first();
        $journalRepos = $this->mock(JournalRepositoryInterface::class)->makePartial();
        $accountRepos = $this->mock(AccountRepositoryInterface::class);

        $journalRepos->shouldReceive('setUser')->once();
        $accountRepos->shouldReceive('setUser');
        $accountRepos->shouldReceive('getAccountsById')->andReturn(new Collection([$account]));
        $journalRepos->shouldReceive('store')->andReturn($journal)->once();
        $data = [
            'description'  => 'Some transaction #' . rand(1, 1000),
            'date'         => '2018-01-01',
            'type'         => 'withdrawal',
            'transactions' => [
                [
                    'amount'      => '10',
                    'currency_id' => 1,
                    'source_id'   => $account->id,
                    'category_id' => $category->id,
                ],


            ],
        ];

        // test API
        $response = $this->post('/api/v1/transactions', $data, ['Accept' => 'application/json']);
        $response->assertStatus(200);
    }

    /**
     * Submit with existing category name, see it reflected in output.
     *
     * @covers \FireflyIII\Api\V1\Controllers\TransactionController::store
     * @covers \FireflyIII\Api\V1\Requests\TransactionRequest
     */
    public function testSuccessStoreCategoryName()
    {
        $category     = $this->user()->categories()->first();
        $journal      = $this->user()->transactionJournals()->first();
        $account      = $this->user()->accounts()->where('account_type_id', 3)->first();
        $journalRepos = $this->mock(JournalRepositoryInterface::class)->makePartial();
        $accountRepos = $this->mock(AccountRepositoryInterface::class);

        $journalRepos->shouldReceive('setUser')->once();
        $accountRepos->shouldReceive('setUser');
        $accountRepos->shouldReceive('getAccountsById')->andReturn(new Collection([$account]));
        $journalRepos->shouldReceive('store')->andReturn($journal)->once();
        $data = [
            'description'  => 'Some transaction #' . rand(1, 1000),
            'date'         => '2018-01-01',
            'type'         => 'withdrawal',
            'transactions' => [
                [
                    'amount'        => '10',
                    'currency_id'   => 1,
                    'source_id'     => $account->id,
                    'category_name' => $category->name,
                ],


            ],
        ];

        // test API
        $response = $this->post('/api/v1/transactions', $data, ['Accept' => 'application/json']);
        $response->assertStatus(200);
    }

    /**
     * Add foreign amount information.
     *
     * @covers \FireflyIII\Api\V1\Controllers\TransactionController::store
     * @covers \FireflyIII\Api\V1\Requests\TransactionRequest
     */
    public function testSuccessStoreForeignAmount()
    {
        $currency     = TransactionCurrency::first();
        $foreign      = TransactionCurrency::where('id', '!=', $currency->id)->first();
        $journal      = $this->user()->transactionJournals()->first();
        $account      = $this->user()->accounts()->where('account_type_id', 3)->first();
        $journalRepos = $this->mock(JournalRepositoryInterface::class)->makePartial();
        $accountRepos = $this->mock(AccountRepositoryInterface::class);

        $journalRepos->shouldReceive('setUser')->once();
        $accountRepos->shouldReceive('setUser');
        $accountRepos->shouldReceive('getAccountsById')->andReturn(new Collection([$account]));
        $journalRepos->shouldReceive('store')->andReturn($journal)->once();
        $data = [
            'description'  => 'Some transaction #' . rand(1, 1000),
            'date'         => '2018-01-01',
            'type'         => 'withdrawal',
            'transactions' => [
                [
                    'amount'              => '10',
                    'currency_id'         => $currency->id,
                    'foreign_currency_id' => $foreign->id,
                    'foreign_amount'      => 23,
                    'source_id'           => $account->id,
                ],


            ],
        ];

        // test API
        $response = $this->post('/api/v1/transactions', $data, ['Accept' => 'application/json']);
        $response->assertStatus(200);
    }

    /**
     * Add all available meta data fields.
     *
     * @covers \FireflyIII\Api\V1\Controllers\TransactionController::store
     * @covers \FireflyIII\Api\V1\Requests\TransactionRequest
     */
    public function testSuccessStoreMetaData()
    {
        $journal      = $this->user()->transactionJournals()->first();
        $account      = $this->user()->accounts()->where('account_type_id', 3)->first();
        $journalRepos = $this->mock(JournalRepositoryInterface::class)->makePartial();
        $accountRepos = $this->mock(AccountRepositoryInterface::class);

        $journalRepos->shouldReceive('setUser')->once();
        $accountRepos->shouldReceive('setUser');
        $accountRepos->shouldReceive('getAccountsById')->andReturn(new Collection([$account]));
        $journalRepos->shouldReceive('store')->andReturn($journal)->once();
        $data = [
            'description'        => 'Some transaction #' . rand(1, 1000),
            'date'               => '2018-01-01',
            'type'               => 'withdrawal',
            // store date meta fields (if present):
            'interest_date'      => '2017-08-02',
            'book_date'          => '2017-08-03',
            'process_date'       => '2017-08-04',
            'due_date'           => '2017-08-05',
            'payment_date'       => '2017-08-06',
            'invoice_date'       => '2017-08-07',
            'internal_reference' => 'I are internal ref!',
            'transactions'       => [
                [
                    'amount'      => '10',
                    'currency_id' => 1,
                    'source_id'   => $account->id,
                ],


            ],
        ];

        // test API
        $response = $this->post('/api/v1/transactions?include=journal_meta', $data, ['Accept' => 'application/json']);
        $response->assertStatus(200);
    }

    /**
     * Submit with NEW category name, see it reflected in output.
     *
     * @covers \FireflyIII\Api\V1\Controllers\TransactionController::store
     * @covers \FireflyIII\Api\V1\Requests\TransactionRequest
     */
    public function testSuccessStoreNewCategoryName()
    {
        $journal      = $this->user()->transactionJournals()->first();
        $account      = $this->user()->accounts()->where('account_type_id', 3)->first();
        $journalRepos = $this->mock(JournalRepositoryInterface::class)->makePartial();
        $accountRepos = $this->mock(AccountRepositoryInterface::class);

        $journalRepos->shouldReceive('setUser')->once();
        $accountRepos->shouldReceive('setUser');
        $accountRepos->shouldReceive('getAccountsById')->andReturn(new Collection([$account]));
        $journalRepos->shouldReceive('store')->andReturn($journal)->once();

        $name = 'Some new category #' . rand(1, 1000);
        $data = [
            'description'  => 'Some transaction #' . rand(1, 1000),
            'date'         => '2018-01-01',
            'type'         => 'withdrawal',
            'transactions' => [
                [
                    'amount'        => '10',
                    'currency_id'   => 1,
                    'source_id'     => $account->id,
                    'category_name' => $name,
                ],


            ],
        ];

        // test API
        $response = $this->post('/api/v1/transactions', $data, ['Accept' => 'application/json']);
        $response->assertStatus(200);
    }

    /**
     * Add opposing account by name.
     *
     * @covers \FireflyIII\Api\V1\Controllers\TransactionController::store
     * @covers \FireflyIII\Api\V1\Requests\TransactionRequest
     */
    public function testSuccessStoreNewOpposingName()
    {
        $journal      = $this->user()->transactionJournals()->first();
        $account      = $this->user()->accounts()->where('account_type_id', 3)->first();
        $opposing     = $this->user()->accounts()->where('account_type_id', 4)->first();
        $journalRepos = $this->mock(JournalRepositoryInterface::class)->makePartial();
        $accountRepos = $this->mock(AccountRepositoryInterface::class);

        $journalRepos->shouldReceive('setUser')->once();
        $accountRepos->shouldReceive('setUser');
        $accountRepos->shouldReceive('getAccountsById')->andReturn(new Collection([$account]), new Collection([$opposing]));
        $journalRepos->shouldReceive('store')->andReturn($journal)->once();
        $name = 'New opposing account #' . rand(1, 10000);
        $data = [
            'description'  => 'Some transaction #' . rand(1, 1000),
            'date'         => '2018-01-01',
            'type'         => 'withdrawal',
            'transactions' => [
                [
                    'amount'           => '10',
                    'currency_id'      => 1,
                    'source_id'        => $account->id,
                    'destination_name' => $name,
                ],


            ],
        ];

        // test API
        $response = $this->post('/api/v1/transactions', $data, ['Accept' => 'application/json']);
        $response->assertStatus(200);
    }

    /**
     * Submit the minimum amount of data required to create a withdrawal.
     *
     * @covers \FireflyIII\Api\V1\Controllers\TransactionController::store
     * @covers \FireflyIII\Api\V1\Requests\TransactionRequest
     */
    public function testSuccessStoreNotes()
    {
        $journal      = $this->user()->transactionJournals()->first();
        $account      = $this->user()->accounts()->where('account_type_id', 3)->first();
        $journalRepos = $this->mock(JournalRepositoryInterface::class)->makePartial();
        $accountRepos = $this->mock(AccountRepositoryInterface::class);

        $journalRepos->shouldReceive('setUser')->once();
        $accountRepos->shouldReceive('setUser');
        $accountRepos->shouldReceive('getAccountsById')->andReturn(new Collection([$account]));
        $journalRepos->shouldReceive('store')->andReturn($journal)->once();

        $data = [
            'description'  => 'Some transaction #' . rand(1, 1000),
            'date'         => '2018-01-01',
            'type'         => 'withdrawal',
            'notes'        => 'I am a note',
            'transactions' => [
                [
                    'amount'      => '10',
                    'currency_id' => 1,
                    'source_id'   => $account->id,
                ],


            ],
        ];

        // test API
        $response = $this->post('/api/v1/transactions', $data, ['Accept' => 'application/json']);
        $response->assertStatus(200);
    }

    /**
     * Add opposing account by ID.
     *
     * @covers \FireflyIII\Api\V1\Controllers\TransactionController::store
     * @covers \FireflyIII\Api\V1\Requests\TransactionRequest
     */
    public function testSuccessStoreOpposingID()
    {
        $opposing     = $this->user()->accounts()->where('account_type_id', 4)->first();
        $journal      = $this->user()->transactionJournals()->first();
        $account      = $this->user()->accounts()->where('account_type_id', 3)->first();
        $journalRepos = $this->mock(JournalRepositoryInterface::class)->makePartial();
        $accountRepos = $this->mock(AccountRepositoryInterface::class);

        $journalRepos->shouldReceive('setUser')->once();
        $accountRepos->shouldReceive('setUser');
        $accountRepos->shouldReceive('getAccountsById')->andReturn(new Collection([$account]), new Collection([$opposing]));
        $journalRepos->shouldReceive('store')->andReturn($journal)->once();
        $data = [
            'description'  => 'Some transaction #' . rand(1, 1000),
            'date'         => '2018-01-01',
            'type'         => 'withdrawal',
            'transactions' => [
                [
                    'amount'         => '10',
                    'currency_id'    => 1,
                    'source_id'      => $account->id,
                    'destination_id' => $opposing->id,
                ],


            ],
        ];

        // test API
        $response = $this->post('/api/v1/transactions', $data, ['Accept' => 'application/json']);
        $response->assertStatus(200);
    }

    /**
     * Add opposing account by name.
     *
     * @covers \FireflyIII\Api\V1\Controllers\TransactionController::store
     * @covers \FireflyIII\Api\V1\Requests\TransactionRequest
     */
    public function testSuccessStoreOpposingName()
    {
        $opposing     = $this->user()->accounts()->where('account_type_id', 4)->first();
        $journal      = $this->user()->transactionJournals()->first();
        $account      = $this->user()->accounts()->where('account_type_id', 3)->first();
        $journalRepos = $this->mock(JournalRepositoryInterface::class)->makePartial();
        $accountRepos = $this->mock(AccountRepositoryInterface::class);

        $journalRepos->shouldReceive('setUser')->once();
        $accountRepos->shouldReceive('setUser');
        $accountRepos->shouldReceive('getAccountsById')->andReturn(new Collection([$account]), new Collection([$opposing]));
        $journalRepos->shouldReceive('store')->andReturn($journal)->once();

        $data = [
            'description'  => 'Some transaction #' . rand(1, 1000),
            'date'         => '2018-01-01',
            'type'         => 'withdrawal',
            'transactions' => [
                [
                    'amount'           => '10',
                    'currency_id'      => 1,
                    'source_id'        => $account->id,
                    'destination_name' => $opposing->name,
                ],


            ],
        ];

        // test API
        $response = $this->post('/api/v1/transactions', $data, ['Accept' => 'application/json']);
        $response->assertStatus(200);
    }

    /**
     * Submit the minimum amount of data required to create a withdrawal.
     * When sending a piggy bank by name, this must be reflected in the output.
     *
     * @covers \FireflyIII\Api\V1\Controllers\TransactionController::store
     * @covers \FireflyIII\Api\V1\Requests\TransactionRequest
     */
    public function testSuccessStorePiggyDeposit()
    {
        $journal      = $this->user()->transactionJournals()->first();
        $account      = $this->user()->accounts()->where('account_type_id', 3)->first();
        $journalRepos = $this->mock(JournalRepositoryInterface::class)->makePartial();
        $accountRepos = $this->mock(AccountRepositoryInterface::class);

        $journalRepos->shouldReceive('setUser')->once();
        $accountRepos->shouldReceive('setUser');
        $accountRepos->shouldReceive('getAccountsById')->andReturn(new Collection([$account]));
        $journalRepos->shouldReceive('store')->andReturn($journal)->once();
        $piggy = $this->user()->piggyBanks()->first();
        $data  = [
            'description'     => 'Some deposit #' . rand(1, 1000),
            'date'            => '2018-01-01',
            'type'            => 'deposit',
            'piggy_bank_name' => $piggy->name,
            'transactions'    => [
                [
                    'amount'         => '10',
                    'currency_id'    => 1,
                    'destination_id' => $account->id,
                ],
            ],
        ];
        // test API
        $response = $this->post('/api/v1/transactions?include=piggy_bank_events', $data, ['Accept' => 'application/json']);
        $this->assertFalse(isset($response->json()['included']));
        $response->assertStatus(200);

    }

    /**
     * Submit the minimum amount of data required to create a withdrawal.
     * When sending a piggy bank by name, this must be reflected in the output.
     *
     * @covers \FireflyIII\Api\V1\Controllers\TransactionController::store
     * @covers \FireflyIII\Api\V1\Requests\TransactionRequest
     */
    public function testSuccessStorePiggyId()
    {
        $source       = $this->user()->accounts()->where('account_type_id', 3)->first();
        $dest         = $this->user()->accounts()->where('account_type_id', 3)->where('id', '!=', $source->id)->first();
        $journal      = $this->user()->transactionJournals()->first();
        $journalRepos = $this->mock(JournalRepositoryInterface::class)->makePartial();
        $accountRepos = $this->mock(AccountRepositoryInterface::class);

        $journalRepos->shouldReceive('setUser')->once();
        $accountRepos->shouldReceive('setUser');
        $accountRepos->shouldReceive('getAccountsById')->andReturn(new Collection([$source]), new Collection([$dest]));
        $journalRepos->shouldReceive('store')->andReturn($journal)->once();

        $piggy = $this->user()->piggyBanks()->first();
        $data  = [
            'description'   => 'Some transfer #' . rand(1, 1000),
            'date'          => '2018-01-01',
            'type'          => 'transfer',
            'piggy_bank_id' => $piggy->id,
            'transactions'  => [
                [
                    'amount'         => '10',
                    'currency_id'    => 1,
                    'source_id'      => $source->id,
                    'destination_id' => $dest->id,
                ],
            ],
        ];
        // test API
        $response = $this->post('/api/v1/transactions?include=piggy_bank_events', $data, ['Accept' => 'application/json']);
        $response->assertStatus(200);
    }

    /**
     * Submit the minimum amount of data required to create a withdrawal.
     * When sending a piggy bank by name, this must be reflected in the output.
     *
     * @covers \FireflyIII\Api\V1\Controllers\TransactionController::store
     * @covers \FireflyIII\Api\V1\Requests\TransactionRequest
     */
    public function testSuccessStorePiggyName()
    {
        $source       = $this->user()->accounts()->where('account_type_id', 3)->first();
        $dest         = $this->user()->accounts()->where('account_type_id', 3)->where('id', '!=', $source->id)->first();
        $journal      = $this->user()->transactionJournals()->first();
        $journalRepos = $this->mock(JournalRepositoryInterface::class)->makePartial();
        $accountRepos = $this->mock(AccountRepositoryInterface::class);

        $journalRepos->shouldReceive('setUser')->once();
        $accountRepos->shouldReceive('setUser');
        $accountRepos->shouldReceive('getAccountsById')->andReturn(new Collection([$source]), new Collection([$dest]));
        $journalRepos->shouldReceive('store')->andReturn($journal)->once();

        $piggy = $this->user()->piggyBanks()->first();
        $data  = [
            'description'     => 'Some transfer #' . rand(1, 1000),
            'date'            => '2018-01-01',
            'type'            => 'transfer',
            'piggy_bank_name' => $piggy->name,
            'transactions'    => [
                [
                    'amount'         => '10',
                    'currency_id'    => 1,
                    'source_id'      => $source->id,
                    'destination_id' => $dest->id,
                ],
            ],
        ];
        // test API
        $response = $this->post('/api/v1/transactions?include=piggy_bank_events', $data, ['Accept' => 'application/json']);
        $response->assertStatus(200);
    }

    /**
     * Set a different reconciled var
     *
     * @covers \FireflyIII\Api\V1\Controllers\TransactionController::store
     * @covers \FireflyIII\Api\V1\Requests\TransactionRequest
     */
    public function testSuccessStoreReconciled()
    {
        $journal      = $this->user()->transactionJournals()->first();
        $account      = $this->user()->accounts()->where('account_type_id', 3)->first();
        $journalRepos = $this->mock(JournalRepositoryInterface::class)->makePartial();
        $accountRepos = $this->mock(AccountRepositoryInterface::class);

        $journalRepos->shouldReceive('setUser')->once();
        $accountRepos->shouldReceive('setUser');
        $accountRepos->shouldReceive('getAccountsById')->andReturn(new Collection([$account]));
        $journalRepos->shouldReceive('store')->andReturn($journal)->once();
        $data = [
            'description'  => 'Some transaction #' . rand(1, 1000),
            'date'         => '2018-01-01',
            'type'         => 'withdrawal',
            'transactions' => [
                [
                    'amount'      => '10',
                    'currency_id' => 1,
                    'source_id'   => $account->id,
                    'reconciled'  => true,
                ],


            ],
        ];

        // test API
        $response = $this->post('/api/v1/transactions', $data, ['Accept' => 'application/json']);
        $response->assertStatus(200);
    }

    /**
     * Submit the data required for a split withdrawal.
     *
     * @covers \FireflyIII\Api\V1\Controllers\TransactionController::store
     * @covers \FireflyIII\Api\V1\Requests\TransactionRequest
     */
    public function testSuccessStoreSplit()
    {
        $journal      = $this->user()->transactionJournals()->first();
        $account      = $this->user()->accounts()->where('account_type_id', 3)->first();
        $journalRepos = $this->mock(JournalRepositoryInterface::class)->makePartial();
        $accountRepos = $this->mock(AccountRepositoryInterface::class);

        $journalRepos->shouldReceive('setUser')->once();
        $accountRepos->shouldReceive('setUser');
        $accountRepos->shouldReceive('getAccountsById')->andReturn(new Collection([$account]));
        $journalRepos->shouldReceive('store')->andReturn($journal)->once();
        $data = [
            'description'  => 'Some transaction #' . rand(1, 1000),
            'date'         => '2018-01-01',
            'type'         => 'withdrawal',
            'transactions' => [
                [
                    'amount'      => '10',
                    'currency_id' => 1,
                    'source_id'   => $account->id,
                    'description' => 'Part 1',
                ],
                [
                    'amount'      => '10',
                    'currency_id' => 1,
                    'source_id'   => $account->id,
                    'description' => 'Part 2',
                ],


            ],
        ];

        // test API
        $response = $this->post('/api/v1/transactions', $data, ['Accept' => 'application/json']);
        $json     = $response->json();
        $response->assertStatus(200);

    }

    /**
     * Submit the minimum amount of data required to create a withdrawal.
     * Add some tags as well. Expect to see them in the result.
     *
     * @covers \FireflyIII\Api\V1\Controllers\TransactionController::store
     * @covers \FireflyIII\Api\V1\Requests\TransactionRequest
     */
    public function testSuccessStoreTags()
    {
        $tags         = [
            'TagOne' . rand(1, 1000),
            'TagTwoBlarg' . rand(1, 1000),
            'SomeThreeTag' . rand(1, 1000),
        ];
        $journal      = $this->user()->transactionJournals()->first();
        $account      = $this->user()->accounts()->where('account_type_id', 3)->first();
        $journalRepos = $this->mock(JournalRepositoryInterface::class)->makePartial();
        $accountRepos = $this->mock(AccountRepositoryInterface::class);

        $journalRepos->shouldReceive('setUser')->once();
        $accountRepos->shouldReceive('setUser');
        $accountRepos->shouldReceive('getAccountsById')->andReturn(new Collection([$account]));
        $journalRepos->shouldReceive('store')->andReturn($journal)->once();
        $data = [
            'description'  => 'Some transaction #' . rand(1, 1000),
            'date'         => '2018-01-01',
            'type'         => 'withdrawal',
            'tags'         => join(',', $tags),
            'transactions' => [
                [
                    'amount'      => '10',
                    'currency_id' => 1,
                    'source_id'   => $account->id,
                ],


            ],
        ];

        // test API
        $response = $this->post('/api/v1/transactions?include=tags', $data, ['Accept' => 'application/json']);
        $response->assertStatus(200);
    }
}