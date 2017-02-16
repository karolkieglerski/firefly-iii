<?php
/**
 * Amount.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace FireflyIII\Support;

use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Models\TransactionJournal;
use Illuminate\Support\Collection;
use Log;
use Preferences as Prefs;

/**
 * Class Amount
 *
 * @package FireflyIII\Support
 */
class Amount
{
    /**
     * bool $sepBySpace is $localeconv['n_sep_by_space']
     * int $signPosn = $localeconv['n_sign_posn']
     * string $sign = $localeconv['negative_sign']
     * bool $csPrecedes = $localeconv['n_cs_precedes']
     *
     * @param bool   $sepBySpace
     * @param int    $signPosn
     * @param string $sign
     * @param bool   $csPrecedes
     *
     * @return string
     */
    public static function getAmountJsConfig(bool $sepBySpace, int $signPosn, string $sign, bool $csPrecedes): string
    {
        // negative first:
        $space = ' ';

        // require space between symbol and amount?
        if (!$sepBySpace) {
            $space = ''; // no
        }

        // there are five possible positions for the "+" or "-" sign (if it is even used)
        // pos_a and pos_e could be the ( and ) symbol.
        $pos_a = ''; // before everything
        $pos_b = ''; // before currency symbol
        $pos_c = ''; // after currency symbol
        $pos_d = ''; // before amount
        $pos_e = ''; // after everything

        // format would be (currency before amount)
        // AB%sC_D%vE
        // or:
        // AD%v_B%sCE (amount before currency)
        // the _ is the optional space


        // switch on how to display amount:
        switch ($signPosn) {
            default:
            case 0:
                // ( and ) around the whole thing
                $pos_a = '(';
                $pos_e = ')';
                break;
            case 1:
                // The sign string precedes the quantity and currency_symbol
                $pos_a = $sign;
                break;
            case 2:
                // The sign string succeeds the quantity and currency_symbol
                $pos_e = $sign;
                break;
            case 3:
                // The sign string immediately precedes the currency_symbol
                $pos_b = $sign;
                break;
            case 4:
                // The sign string immediately succeeds the currency_symbol
                $pos_c = $sign;
        }

        // default is amount before currency
        $format = $pos_a . $pos_d . '%v' . $space . $pos_b . '%s' . $pos_c . $pos_e;

        if ($csPrecedes) {
            // alternative is currency before amount
            $format = $pos_a . $pos_b . '%s' . $pos_c . $space . $pos_d . '%v' . $pos_e;
        }
        Log::debug(sprintf('Final format: "%s"', $format));

        return $format;
    }

    /**
     * @param string $amount
     * @param bool   $coloured
     *
     * @return string
     */
    public function format(string $amount, bool $coloured = true): string
    {
        return $this->formatAnything($this->getDefaultCurrency(), $amount, $coloured);
    }

    /**
     * This method will properly format the given number, in color or "black and white",
     * as a currency, given two things: the currency required and the current locale.
     *
     * @param \FireflyIII\Models\TransactionCurrency $format
     * @param string                                 $amount
     * @param bool                                   $coloured
     *
     * @return string
     */
    public function formatAnything(TransactionCurrency $format, string $amount, bool $coloured = true): string
    {
        $locale = explode(',', trans('config.locale'));
        $locale = array_map('trim', $locale);
        setlocale(LC_MONETARY, $locale);
        $float     = round($amount, 12);
        $info      = localeconv();
        $formatted = number_format($float, $format->decimal_places, $info['mon_decimal_point'], $info['mon_thousands_sep']);

        // some complicated switches to format the amount correctly:
        $precedes  = $amount < 0 ? $info['n_cs_precedes'] : $info['p_cs_precedes'];
        $separated = $amount < 0 ? $info['n_sep_by_space'] : $info['p_sep_by_space'];
        $space     = $separated ? ' ' : '';
        $result    = $format->symbol . $space . $formatted;

        if (!$precedes) {
            $result = $space . $formatted . $format->symbol;
        }

        if ($coloured === true) {

            if ($amount > 0) {
                return sprintf('<span class="text-success">%s</span>', $result);
            } else {
                if ($amount < 0) {
                    return sprintf('<span class="text-danger">%s</span>', $result);
                }
            }

            return sprintf('<span style="color:#999">%s</span>', $result);


        }

        return $result;
    }

    /**
     * Used in many places (unfortunately).
     *
     * @param string $currencyCode
     * @param string $amount
     * @param bool   $coloured
     *
     * @return string
     */
    public function formatByCode(string $currencyCode, string $amount, bool $coloured = true): string
    {
        $currency = TransactionCurrency::whereCode($currencyCode)->first();

        return $this->formatAnything($currency, $amount, $coloured);
    }

    /**
     *
     * @param \FireflyIII\Models\TransactionJournal $journal
     * @param bool                                  $coloured
     *
     * @return string
     */
    public function formatJournal(TransactionJournal $journal, bool $coloured = true): string
    {
        $currency = $journal->transactionCurrency;

        return $this->formatAnything($currency, TransactionJournal::amount($journal), $coloured);
    }

    /**
     * @param Transaction $transaction
     * @param bool        $coloured
     *
     * @return string
     */
    public function formatTransaction(Transaction $transaction, bool $coloured = true)
    {
        $currency = $transaction->transactionJournal->transactionCurrency;

        return $this->formatAnything($currency, strval($transaction->amount), $coloured);
    }

    /**
     * @return Collection
     */
    public function getAllCurrencies(): Collection
    {
        return TransactionCurrency::orderBy('code', 'ASC')->get();
    }

    /**
     * @return string
     */
    public function getCurrencyCode(): string
    {

        $cache = new CacheProperties;
        $cache->addProperty('getCurrencyCode');
        if ($cache->has()) {
            return $cache->get();
        } else {
            $currencyPreference = Prefs::get('currencyPreference', config('firefly.default_currency', 'EUR'));

            $currency = TransactionCurrency::whereCode($currencyPreference->data)->first();
            if ($currency) {

                $cache->store($currency->code);

                return $currency->code;
            }
            $cache->store(config('firefly.default_currency', 'EUR'));

            return config('firefly.default_currency', 'EUR');
        }
    }

    /**
     * @return string
     */
    public function getCurrencySymbol(): string
    {
        $cache = new CacheProperties;
        $cache->addProperty('getCurrencySymbol');
        if ($cache->has()) {
            return $cache->get();
        } else {
            $currencyPreference = Prefs::get('currencyPreference', config('firefly.default_currency', 'EUR'));
            $currency           = TransactionCurrency::whereCode($currencyPreference->data)->first();

            $cache->store($currency->symbol);

            return $currency->symbol;
        }
    }

    /**
     * @return \FireflyIII\Models\TransactionCurrency
     */
    public function getDefaultCurrency(): TransactionCurrency
    {
        $cache = new CacheProperties;
        $cache->addProperty('getDefaultCurrency');
        if ($cache->has()) {
            return $cache->get();
        }
        $currencyPreference = Prefs::get('currencyPreference', config('firefly.default_currency', 'EUR'));
        $currency           = TransactionCurrency::whereCode($currencyPreference->data)->first();
        $cache->store($currency);

        return $currency;
    }

    /**
     * This method returns the correct format rules required by accounting.js,
     * the library used to format amounts in charts.
     *
     * @param array $config
     *
     * @return array
     */
    public function getJsConfig(array $config): array
    {
        $negative = self::getAmountJsConfig($config['n_sep_by_space'] === 1, $config['n_sign_posn'], $config['negative_sign'], $config['n_cs_precedes'] === 1);
        $positive = self::getAmountJsConfig($config['p_sep_by_space'] === 1, $config['p_sign_posn'], $config['positive_sign'], $config['p_cs_precedes'] === 1);

        return [
            'pos'  => $positive,
            'neg'  => $negative,
            'zero' => $positive,
        ];
    }
}
