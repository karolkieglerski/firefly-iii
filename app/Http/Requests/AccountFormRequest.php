<?php
/**
 * AccountFormRequest.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace FireflyIII\Http\Requests;

use Carbon\Carbon;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;

/**
 * Class AccountFormRequest
 *
 *
 * @package FireflyIII\Http\Requests
 */
class AccountFormRequest extends Request
{
    /**
     * @return bool
     */
    public function authorize()
    {
        // Only allow logged in users
        return auth()->check();
    }

    /**
     * @return array
     */
    public function getAccountData(): array
    {
        return [
            'name'                   => trim($this->input('name')),
            'active'                 => intval($this->input('active')) === 1,
            'accountType'            => $this->input('what'),
            'virtualBalance'         => round($this->input('virtualBalance'), 2),
            'virtualBalanceCurrency' => intval($this->input('amount_currency_id_virtualBalance')),
            'iban'                   => trim($this->input('iban')),
            'accountNumber'          => trim($this->input('accountNumber')),
            'accountRole'            => $this->input('accountRole'),
            'openingBalance'         => round($this->input('openingBalance'), 2),
            'openingBalanceDate'     => new Carbon((string)$this->input('openingBalanceDate')),
            'openingBalanceCurrency' => intval($this->input('amount_currency_id_openingBalance')),
            'ccType'                 => $this->input('ccType'),
            'ccMonthlyPaymentDate'   => $this->input('ccMonthlyPaymentDate'),
        ];
    }

    /**
     * @return array
     */
    public function rules()
    {
        /** @var AccountRepositoryInterface $repository */
        $repository     = app(AccountRepositoryInterface::class);
        $accountRoles   = join(',', array_keys(config('firefly.accountRoles')));
        $types          = join(',', array_keys(config('firefly.subTitlesByIdentifier')));
        $ccPaymentTypes = join(',', array_keys(config('firefly.ccTypes')));

        $nameRule = 'required|min:1|uniqueAccountForUser';
        $idRule   = '';
        if (!is_null($repository->find(intval($this->get('id')))->id)) {
            $idRule   = 'belongsToUser:accounts';
            $nameRule = 'required|min:1|uniqueAccountForUser:' . $this->get('id');
        }

        return [
            'id'                                => $idRule,
            'name'                              => $nameRule,
            'openingBalance'                    => 'numeric',
            'iban'                              => 'iban',
            'virtualBalance'                    => 'numeric',
            'openingBalanceDate'                => 'date',
            'accountNumber'                     => 'between:1,255|uniqueAccountNumberForUser',
            'accountRole'                       => 'in:' . $accountRoles,
            'active'                            => 'boolean',
            'ccType'                            => 'in:' . $ccPaymentTypes,
            'ccMonthlyPaymentDate'              => 'date',
            'amount_currency_id_openingBalance' => 'exists:transaction_currencies,id',
            'amount_currency_id_virtualBalance' => 'exists:transaction_currencies,id',
            'what'                              => 'in:' . $types,
        ];
    }
}
