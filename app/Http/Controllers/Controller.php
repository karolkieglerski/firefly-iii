<?php
/**
 * Controller.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace FireflyIII\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use View;

/**
 * Class Controller
 *
 * @package FireflyIII\Http\Controllers
 */
class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    /** @var  string */
    protected $dateTimeFormat;
    /** @var string */
    protected $monthAndDayFormat;
    /** @var string */
    protected $monthFormat;

    /**
     * Controller constructor.
     */
    public function __construct()
    {
        View::share('hideBudgets', false);
        View::share('hideCategories', false);
        View::share('hideBills', false);
        View::share('hideTags', false);


        // translations:

        $this->middleware(
            function ($request, $next) {
                $this->monthFormat       = (string)trans('config.month');
                $this->monthAndDayFormat = (string)trans('config.month_and_day');
                $this->dateTimeFormat    = (string)trans('config.date_time');

                return $next($request);
            }
        );

    }

}
