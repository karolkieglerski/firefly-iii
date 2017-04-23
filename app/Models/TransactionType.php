<?php
/**
 * TransactionType.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

namespace FireflyIII\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class TransactionType
 *
 * @package FireflyIII\Models
 */
class TransactionType extends Model
{
    use SoftDeletes;

    const WITHDRAWAL      = 'Withdrawal';
    const DEPOSIT         = 'Deposit';
    const TRANSFER        = 'Transfer';
    const OPENING_BALANCE = 'Opening balance';

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts
        = [
            'created_at' => 'date',
            'updated_at' => 'date',
            'deleted_at' => 'date',
        ];

    protected $dates = ['created_at', 'updated_at', 'deleted_at'];

    /**
     * @param string $type
     *
     * @return Model|null|static
     */
    public static function routeBinder(string $type)
    {
        if (!auth()->check()) {
            throw new NotFoundHttpException;
        }
        $transactionType = self::where('type', ucfirst($type))->first();
        if (!is_null($transactionType)) {
            return $transactionType;
        }
        throw new NotFoundHttpException;

    }


    /**
     * @return bool
     */
    public function isDeposit()
    {
        return $this->type === self::DEPOSIT;
    }

    /**
     * @return bool
     */
    public function isOpeningBalance()
    {
        return $this->type === self::OPENING_BALANCE;
    }

    /**
     * @return bool
     */
    public function isTransfer()
    {
        return $this->type === self::TRANSFER;
    }

    /**
     * @return bool
     */
    public function isWithdrawal()
    {
        return $this->type === self::WITHDRAWAL;
    }

    /**
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function transactionJournals()
    {
        return $this->hasMany('FireflyIII\Models\TransactionJournal');
    }
}
