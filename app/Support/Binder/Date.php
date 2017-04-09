<?php
/**
 * Date.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

namespace FireflyIII\Support\Binder;

use Carbon\Carbon;
use Exception;
use FireflyIII\Helpers\FiscalHelper;
use Log;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class Date
 *
 * @package FireflyIII\Support\Binder
 */
class Date implements BinderInterface
{


    /**
     * @param $value
     * @param $route
     *
     * @return mixed
     */
    public static function routeBinder($value, $route): Carbon
    {
        $fiscalHelper = new FiscalHelper;

        switch ($value) {
            default:
                try {
                    $date = new Carbon($value);
                } catch (Exception $e) {
                    Log::error('Could not parse date "' . $value . '" for user #' . auth()->user()->id);
                    throw new NotFoundHttpException;
                }

                return $date;
            case 'currentMonthStart':
                return Carbon::now()->startOfMonth();
            case 'currentMonthEnd':
                return Carbon::now()->endOfMonth();
            case 'currentYearStart':
                return Carbon::now()->startOfYear();
            case 'currentYearEnd':
                return Carbon::now()->endOfYear();
            case 'currentFiscalYearStart':
                return $fiscalHelper->startOfFiscalYear(Carbon::now());
            case 'currentFiscalYearEnd':
                return $fiscalHelper->endOfFiscalYear(Carbon::now());

        }
    }
}
