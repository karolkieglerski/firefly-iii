<?php
/**
 * TransactionCurrency.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace FireflyIII\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Watson\Validating\ValidatingTrait;

/**
 * Class TransactionCurrency
 *
 * @package FireflyIII\Models
 */
class TransactionCurrency extends Model
{
    use SoftDeletes, ValidatingTrait;


    protected $dates    = ['created_at', 'updated_at', 'deleted_at'];
    protected $fillable = ['name', 'code', 'symbol'];
    protected $rules    = ['name' => 'required|between:1,200', 'code' => 'required|between:3,3', 'symbol' => 'required|between:1,12'];

    /**
     * @param TransactionCurrency $currency
     *
     * @return TransactionCurrency
     */
    public static function routeBinder(TransactionCurrency $currency)
    {
        if (auth()->check()) {
            return $currency;
        }
        throw new NotFoundHttpException;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function transactionJournals()
    {
        return $this->hasMany('FireflyIII\Models\TransactionJournal');
    }
}
