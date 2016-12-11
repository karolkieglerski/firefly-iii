<?php
/**
 * FireflyServiceProvider.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types = 1);

namespace FireflyIII\Providers;

use FireflyIII\Support\Amount;
use FireflyIII\Support\ExpandedForm;
use FireflyIII\Support\FireflyConfig;
use FireflyIII\Support\Navigation;
use FireflyIII\Support\Preferences;
use FireflyIII\Support\Steam;
use FireflyIII\Support\Twig\General;
use FireflyIII\Support\Twig\Journal;
use FireflyIII\Support\Twig\PiggyBank;
use FireflyIII\Support\Twig\Rule;
use FireflyIII\Support\Twig\Transaction;
use FireflyIII\Support\Twig\Translation;
use FireflyIII\Validation\FireflyValidator;
use Illuminate\Support\ServiceProvider;
use Twig;
use TwigBridge\Extension\Loader\Functions;
use Validator;

/**
 * Class FireflyServiceProvider
 *
 * @package FireflyIII\Providers
 */
class FireflyServiceProvider extends ServiceProvider
{
    public function boot()
    {
        Validator::resolver(
            function ($translator, $data, $rules, $messages) {
                return new FireflyValidator($translator, $data, $rules, $messages);
            }
        );
        $config = app('config');
        Twig::addExtension(new Functions($config));
        Twig::addExtension(new PiggyBank);
        Twig::addExtension(new General);
        Twig::addExtension(new Journal);
        Twig::addExtension(new Translation);
        Twig::addExtension(new Transaction);
        Twig::addExtension(new Rule);
    }

    /**
     *
     */
    public function register()
    {
        $this->app->bind(
            'preferences', function () {
            return new Preferences;
        }
        );

        $this->app->bind(
            'fireflyconfig', function () {
            return new FireflyConfig;
        }
        );
        $this->app->bind(
            'navigation', function () {
            return new Navigation;
        }
        );
        $this->app->bind(
            'amount', function () {
            return new Amount;
        }
        );

        $this->app->bind(
            'steam', function () {
            return new Steam;
        }
        );
        $this->app->bind(
            'expandedform', function () {
            return new ExpandedForm;
        }
        );

        // chart generator:
        $this->app->bind('FireflyIII\Generator\Chart\Basic\GeneratorInterface', 'FireflyIII\Generator\Chart\Basic\ChartJsGenerator');

        // other generators
        $this->app->bind('FireflyIII\Repositories\User\UserRepositoryInterface', 'FireflyIII\Repositories\User\UserRepository');
        $this->app->bind('FireflyIII\Helpers\Attachments\AttachmentHelperInterface', 'FireflyIII\Helpers\Attachments\AttachmentHelper');
        $this->app->bind('FireflyIII\Generator\Chart\Bill\BillChartGeneratorInterface', 'FireflyIII\Generator\Chart\Bill\ChartJsBillChartGenerator');
        $this->app->bind('FireflyIII\Generator\Chart\Budget\BudgetChartGeneratorInterface', 'FireflyIII\Generator\Chart\Budget\ChartJsBudgetChartGenerator');
        $this->app->bind(
            'FireflyIII\Generator\Chart\Category\CategoryChartGeneratorInterface', 'FireflyIII\Generator\Chart\Category\ChartJsCategoryChartGenerator'
        );
        $this->app->bind(
            'FireflyIII\Generator\Chart\PiggyBank\PiggyBankChartGeneratorInterface', 'FireflyIII\Generator\Chart\PiggyBank\ChartJsPiggyBankChartGenerator'
        );
        $this->app->bind('FireflyIII\Generator\Chart\Report\ReportChartGeneratorInterface', 'FireflyIII\Generator\Chart\Report\ChartJsReportChartGenerator');
        $this->app->bind('FireflyIII\Helpers\Help\HelpInterface', 'FireflyIII\Helpers\Help\Help');
        $this->app->bind('FireflyIII\Helpers\Report\ReportHelperInterface', 'FireflyIII\Helpers\Report\ReportHelper');
        $this->app->bind('FireflyIII\Helpers\FiscalHelperInterface', 'FireflyIII\Helpers\FiscalHelper');
        $this->app->bind('FireflyIII\Helpers\Report\AccountReportHelperInterface', 'FireflyIII\Helpers\Report\AccountReportHelper');
        $this->app->bind('FireflyIII\Helpers\Report\BalanceReportHelperInterface', 'FireflyIII\Helpers\Report\BalanceReportHelper');
        $this->app->bind('FireflyIII\Helpers\Report\BudgetReportHelperInterface', 'FireflyIII\Helpers\Report\BudgetReportHelper');
    }

}
