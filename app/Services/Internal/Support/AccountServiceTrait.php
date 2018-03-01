<?php
/**
 * AccountServiceTrait.php
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

namespace FireflyIII\Services\Internal\Support;

use Exception;
use FireflyIII\Factory\AccountFactory;
use FireflyIII\Factory\AccountMetaFactory;
use FireflyIII\Factory\TransactionFactory;
use FireflyIII\Factory\TransactionJournalFactory;
use FireflyIII\Models\Account;
use FireflyIII\Models\AccountMeta;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\Note;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\TransactionType;
use FireflyIII\User;
use Log;
use Validator;

/**
 * Trait AccountServiceTrait
 *
 * @package FireflyIII\Services\Internal\Support
 */
trait AccountServiceTrait
{
    /** @var array */
    public $validAssetFields = ['accountRole', 'accountNumber', 'currency_id', 'BIC'];
    /** @var array */
    public $validCCFields = ['accountRole', 'ccMonthlyPaymentDate', 'ccType', 'accountNumber', 'currency_id', 'BIC'];
    /** @var array */
    public $validFields = ['accountNumber', 'currency_id', 'BIC'];

    /**
     * @param Account $account
     *
     * @return bool
     */
    public function deleteIB(Account $account): bool
    {
        Log::debug(sprintf('deleteIB() for account #%d', $account->id));
        $openingBalance = $this->getIBJournal($account);

        // opening balance data? update it!
        if (null !== $openingBalance) {
            Log::debug('Opening balance journal found, delete journal.');
            try {
                $openingBalance->delete();
            } catch (Exception $e) {
                Log::error(sprintf('Could not delete opening balance: %s', $e->getMessage()));
            }

            return true;
        }

        return true;
    }

    /**
     * @param null|string $iban
     *
     * @return null|string
     */
    public function filterIban(?string $iban)
    {
        if (null === $iban) {
            return null;
        }
        $data      = ['iban' => $iban];
        $rules     = ['iban' => 'required|iban'];
        $validator = Validator::make($data, $rules);
        if ($validator->fails()) {
            Log::error(sprintf('Detected invalid IBAN ("%s"). Return NULL instead.', $iban));

            return null;
        }


        return $iban;
    }

    /**
     * Find existing opening balance.
     *
     * @param Account $account
     *
     * @return TransactionJournal|null
     */
    public function getIBJournal(Account $account): ?TransactionJournal
    {
        $journal = TransactionJournal::leftJoin('transactions', 'transactions.transaction_journal_id', '=', 'transaction_journals.id')
                                     ->where('transactions.account_id', $account->id)
                                     ->transactionTypes([TransactionType::OPENING_BALANCE])
                                     ->first(['transaction_journals.*']);
        if (null === $journal) {
            Log::debug('Could not find a opening balance journal, return NULL.');

            return null;
        }
        Log::debug(sprintf('Found opening balance: journal #%d.', $journal->id));

        return $journal;
    }

    /**
     * @param Account $account
     * @param array   $data
     *
     * @return TransactionJournal|null
     */
    public function storeIBJournal(Account $account, array $data): ?TransactionJournal
    {
        $amount = strval($data['openingBalance']);
        Log::debug(sprintf('Submitted amount is %s', $amount));

        if (0 === bccomp($amount, '0')) {
            return null;
        }

        // store journal, without transactions:
        $name        = $data['name'];
        $currencyId  = $data['currency_id'];
        $journalData = [
            'type'                    => TransactionType::OPENING_BALANCE,
            'user'                    => $account->user->id,
            'transaction_currency_id' => $currencyId,
            'description'             => strval(trans('firefly.initial_balance_description', ['account' => $account->name])),
            'completed'               => true,
            'date'                    => $data['openingBalanceDate'],
            'bill_id'                 => null,
            'bill_name'               => null,
            'piggy_bank_id'           => null,
            'piggy_bank_name'         => null,
            'tags'                    => null,
            'notes'                   => null,
            'transactions'            => [],

        ];
        /** @var TransactionJournalFactory $factory */
        $factory = app(TransactionJournalFactory::class);
        $factory->setUser($account->user);
        $journal  = $factory->create($journalData);
        $opposing = $this->storeOpposingAccount($account->user, $name);
        Log::notice(sprintf('Created new opening balance journal: #%d', $journal->id));

        $firstAccount  = $account;
        $secondAccount = $opposing;
        $firstAmount   = $amount;
        $secondAmount  = bcmul($amount, '-1');
        Log::notice(sprintf('First amount is %s, second amount is %s', $firstAmount, $secondAmount));

        if (bccomp($amount, '0') === -1) {
            Log::debug(sprintf('%s is a negative number.', $amount));
            $firstAccount  = $opposing;
            $secondAccount = $account;
            $firstAmount   = bcmul($amount, '-1');
            $secondAmount  = $amount;
            Log::notice(sprintf('First amount is %s, second amount is %s', $firstAmount, $secondAmount));
        }
        /** @var TransactionFactory $factory */
        $factory = app(TransactionFactory::class);
        $factory->setUser($account->user);
        $one = $factory->create(
            [
                'account'             => $firstAccount,
                'transaction_journal' => $journal,
                'amount'              => $firstAmount,
                'currency_id'         => $currencyId,
                'description'         => null,
                'identifier'          => 0,
                'foreign_amount'      => null,
                'reconciled'          => false,
            ]
        );
        $two = $factory->create(
            [
                'account'             => $secondAccount,
                'transaction_journal' => $journal,
                'amount'              => $secondAmount,
                'currency_id'         => $currencyId,
                'description'         => null,
                'identifier'          => 0,
                'foreign_amount'      => null,
                'reconciled'          => false,
            ]
        );
        Log::notice(sprintf('Stored two transactions for new account, #%d and #%d', $one->id, $two->id));

        return $journal;
    }

    /**
     * TODO make sure this works (user ID, etc.)
     *
     * @param User   $user
     * @param string $name
     *
     * @return Account
     */
    public function storeOpposingAccount(User $user, string $name): Account
    {
        $name = $name . ' initial balance';
        Log::debug('Going to create an opening balance opposing account.');
        /** @var AccountFactory $factory */
        $factory = app(AccountFactory::class);
        $factory->setUser($user);

        return $factory->findOrCreate($name, AccountType::INITIAL_BALANCE);
    }

    /**
     * @param Account $account
     * @param array   $data
     *
     * @return bool
     */
    public function updateIB(Account $account, array $data): bool
    {
        Log::debug(sprintf('updateInitialBalance() for account #%d', $account->id));
        $openingBalance = $this->getIBJournal($account);

        // no opening balance journal? create it:
        if (null === $openingBalance) {
            Log::debug('No opening balance journal yet, create journal.');
            $this->storeIBJournal($account, $data);

            return true;
        }

        // opening balance data? update it!
        if (null !== $openingBalance->id) {
            Log::debug('Opening balance journal found, update journal.');
            $this->updateIBJournal($account, $openingBalance, $data);

            return true;
        }

        return true;
    }

    /**
     * @param Account            $account
     * @param TransactionJournal $journal
     * @param array              $data
     *
     * @return bool
     */
    public function updateIBJournal(Account $account, TransactionJournal $journal, array $data): bool
    {
        $date           = $data['openingBalanceDate'];
        $amount         = strval($data['openingBalance']);
        $negativeAmount = bcmul($amount, '-1');
        $currencyId     = intval($data['currency_id']);

        Log::debug(sprintf('Submitted amount for opening balance to update is "%s"', $amount));
        if (0 === bccomp($amount, '0')) {
            Log::notice(sprintf('Amount "%s" is zero, delete opening balance.', $amount));
            try {
                $journal->delete();
            } catch (Exception $e) {
                Log::error(sprintf('Could not delete opening balance: %s', $e->getMessage()));
            }


            return true;
        }

        // update date:
        $journal->date                    = $date;
        $journal->transaction_currency_id = $currencyId;
        $journal->save();

        // update transactions:
        /** @var Transaction $transaction */
        foreach ($journal->transactions()->get() as $transaction) {
            if (intval($account->id) === intval($transaction->account_id)) {
                Log::debug(sprintf('Will (eq) change transaction #%d amount from "%s" to "%s"', $transaction->id, $transaction->amount, $amount));
                $transaction->amount                  = $amount;
                $transaction->transaction_currency_id = $currencyId;
                $transaction->save();
            }
            if (!(intval($account->id) === intval($transaction->account_id))) {
                Log::debug(sprintf('Will (neq) change transaction #%d amount from "%s" to "%s"', $transaction->id, $transaction->amount, $negativeAmount));
                $transaction->amount                  = $negativeAmount;
                $transaction->transaction_currency_id = $currencyId;
                $transaction->save();
            }
        }
        Log::debug('Updated opening balance journal.');

        return true;
    }

    /**
     * Update meta data for account. Depends on type which fields are valid.
     *
     * @param Account $account
     * @param array   $data
     */
    public function updateMetaData(Account $account, array $data)
    {
        $fields = $this->validFields;

        if ($account->accountType->type === AccountType::ASSET) {
            $fields = $this->validAssetFields;
        }
        if ($account->accountType->type === AccountType::ASSET && $data['accountRole'] === 'ccAsset') {
            $fields = $this->validCCFields;
        }
        /** @var AccountMetaFactory $factory */
        $factory = app(AccountMetaFactory::class);
        foreach ($fields as $field) {
            /** @var AccountMeta $entry */
            $entry = $account->accountMeta()->where('name', $field)->first();

            // if $data has field and $entry is null, create new one:
            if (isset($data[$field]) && null === $entry) {
                Log::debug(sprintf('Created meta-field "%s":"%s" for account #%d ("%s") ', $field, $data[$field], $account->id, $account->name));
                $factory->create(['account_id' => $account->id, 'name' => $field, 'data' => $data[$field],]);
            }

            // if $data has field and $entry is not null, update $entry:
            // let's not bother with a service.
            if (isset($data[$field]) && null !== $entry) {
                $entry->data = $data[$field];
                $entry->save();
                Log::debug(sprintf('Updated meta-field "%s":"%s" for #%d ("%s") ', $field, $data[$field], $account->id, $account->name));
            }
        }
    }

    /**
     * @param Account $account
     * @param string  $note
     *
     * @return bool
     */
    public function updateNote(Account $account, string $note): bool
    {
        if (0 === strlen($note)) {
            $dbNote = $account->notes()->first();
            if (null !== $dbNote) {
                $dbNote->delete();
            }

            return true;
        }
        $dbNote = $account->notes()->first();
        if (null === $dbNote) {
            $dbNote = new Note;
            $dbNote->noteable()->associate($account);
        }
        $dbNote->text = trim($note);
        $dbNote->save();

        return true;
    }

    /**
     * Verify if array contains valid data to possibly store or update the opening balance.
     *
     * @param array $data
     *
     * @return bool
     */
    public function validIBData(array $data): bool
    {
        $data['openingBalance'] = strval($data['openingBalance'] ?? '');
        if (isset($data['openingBalance']) && null !== $data['openingBalance'] && strlen($data['openingBalance']) > 0
            && isset($data['openingBalanceDate'])) {
            Log::debug('Array has valid opening balance data.');

            return true;
        }
        Log::debug('Array does not have valid opening balance data.');

        return false;
    }
}