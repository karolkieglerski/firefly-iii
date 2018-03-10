<?php
/**
 * firefly.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
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

/*
 * DO NOT EDIT THIS FILE. IT IS AUTO GENERATED.
 *
 * ANY OPTIONS IN THIS FILE YOU CAN SAFELY EDIT CAN BE FOUND IN THE USER INTERFACE OF FIRFELY III.
 */

return [
    'configuration'  => [
        'single_user_mode' => true,
        'is_demo_site'     => false,
    ],
    'encryption'     => (is_null(env('USE_ENCRYPTION')) || env('USE_ENCRYPTION') === true),
    'version'        => '4.7.1.4',
    'api_version'    => '0.1',
    'db_version'     => 2,
    'maxUploadSize'  => 15242880,
    'allowedMimes'   => [
        /* plain files */
        'text/plain',

        /* images */
        'image/jpeg',
        'image/svg+xml',
        'image/png',
        'image/heic',
        'image/heic-sequence',

        /* PDF */
        'application/pdf',


        /* MS word */
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.template',
        /* MS excel */
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.template',
        /* MS powerpoint */
        'application/vnd.ms-powerpoint',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'application/vnd.openxmlformats-officedocument.presentationml.template',
        'application/vnd.openxmlformats-officedocument.presentationml.slideshow',
        /* iWork */
        'application/x-iwork-pages-sffpages',
        /* open office */
        'application/vnd.sun.xml.writer',
        'application/vnd.sun.xml.writer.template',
        'application/vnd.sun.xml.writer.global',
        'application/vnd.stardivision.writer',
        'application/vnd.stardivision.writer-global',
        'application/vnd.sun.xml.calc',
        'application/vnd.sun.xml.calc.template',
        'application/vnd.stardivision.calc',
        'application/vnd.sun.xml.impress',
        'application/vnd.sun.xml.impress.template',
        'application/vnd.stardivision.impress',
        'application/vnd.sun.xml.draw',
        'application/vnd.sun.xml.draw.template',
        'application/vnd.stardivision.draw',
        'application/vnd.sun.xml.math',
        'application/vnd.stardivision.math',
        'application/vnd.oasis.opendocument.text',
        'application/vnd.oasis.opendocument.text-template',
        'application/vnd.oasis.opendocument.text-web',
        'application/vnd.oasis.opendocument.text-master',
        'application/vnd.oasis.opendocument.graphics',
        'application/vnd.oasis.opendocument.graphics-template',
        'application/vnd.oasis.opendocument.presentation',
        'application/vnd.oasis.opendocument.presentation-template',
        'application/vnd.oasis.opendocument.spreadsheet',
        'application/vnd.oasis.opendocument.spreadsheet-template',
        'application/vnd.oasis.opendocument.chart',
        'application/vnd.oasis.opendocument.formula',
        'application/vnd.oasis.opendocument.database',
        'application/vnd.oasis.opendocument.image',
    ],
    'list_length'    => 10,
    'export_formats' => [
        'csv' => 'FireflyIII\Export\Exporter\CsvExporter',
    ],
    'spectre'        => [
        'server' => 'https://www.saltedge.com',
    ],

    'default_export_format'      => 'csv',
    'default_import_format'      => 'csv',
    'bill_periods'               => ['weekly', 'monthly', 'quarterly', 'half-year', 'yearly'],
    'accountRoles'               => ['defaultAsset', 'sharedAsset', 'savingAsset', 'ccAsset',],
    'ccTypes'                    => [
        'monthlyFull' => 'Full payment every month',
    ],
    'range_to_repeat_freq'       => [
        '1D'     => 'weekly',
        '1W'     => 'weekly',
        '1M'     => 'monthly',
        '3M'     => 'quarterly',
        '6M'     => 'half-year',
        '1Y'     => 'yearly',
        'custom' => 'custom',
    ],
    'subTitlesByIdentifier'      =>
        [
            'asset'   => 'Asset accounts',
            'expense' => 'Expense accounts',
            'revenue' => 'Revenue accounts',
            'cash'    => 'Cash accounts',
        ],
    'subIconsByIdentifier'       =>
        [
            'asset'               => 'fa-money',
            'Asset account'       => 'fa-money',
            'Default account'     => 'fa-money',
            'Cash account'        => 'fa-money',
            'expense'             => 'fa-shopping-cart',
            'Expense account'     => 'fa-shopping-cart',
            'Beneficiary account' => 'fa-shopping-cart',
            'revenue'             => 'fa-download',
            'Revenue account'     => 'fa-download',
            'import'              => 'fa-download',
            'Import account'      => 'fa-download',
        ],
    'accountTypesByIdentifier'   =>
        [
            'asset'   => ['Default account', 'Asset account'],
            'expense' => ['Expense account', 'Beneficiary account'],
            'revenue' => ['Revenue account'],
            'import'  => ['Import account'],
        ],
    'accountTypeByIdentifier'    =>
        [
            'asset'     => 'Asset account',
            'expense'   => 'Expense account',
            'revenue'   => 'Revenue account',
            'opening'   => 'Initial balance account',
            'initial'   => 'Initial balance account',
            'import'    => 'Import account',
            'reconcile' => 'Reconciliation account',
        ],
    'shortNamesByFullName'       =>
        [
            'Default account'     => 'asset',
            'Asset account'       => 'asset',
            'Import account'      => 'import',
            'Expense account'     => 'expense',
            'Beneficiary account' => 'expense',
            'Revenue account'     => 'revenue',
            'Cash account'        => 'cash',
        ],
    'languages'                  => [
        // completed languages
        'en_US' => ['name_locale' => 'English', 'name_english' => 'English'],
        'es_ES' => ['name_locale' => 'Español', 'name_english' => 'Spanish'],
        'de_DE' => ['name_locale' => 'Deutsch', 'name_english' => 'German'],
        'fr_FR' => ['name_locale' => 'Français', 'name_english' => 'French'],
        'id_ID' => ['name_locale' => 'Bahasa Indonesia', 'name_english' => 'Indonesian'],
        'nl_NL' => ['name_locale' => 'Nederlands', 'name_english' => 'Dutch'],
        'pl_PL' => ['name_locale' => 'Polski', 'name_english' => 'Polish '],
        'pt_BR' => ['name_locale' => 'Português do Brasil', 'name_english' => 'Portuguese (Brazil)'],
        'ru_RU' => ['name_locale' => 'Русский', 'name_english' => 'Russian'],
        'tr_TR' => ['name_locale' => 'Türkçe', 'name_english' => 'Turkish'],

        // incomplete languages:
        // 'ca_ES' => ['name_locale' => 'Català', 'name_english' => 'Catalan'],
    ],
    'transactionTypesByWhat'     => [
        'expenses'   => ['Withdrawal'],
        'withdrawal' => ['Withdrawal'],
        'revenue'    => ['Deposit'],
        'deposit'    => ['Deposit'],
        'transfer'   => ['Transfer'],
        'transfers'  => ['Transfer'],
    ],
    'transactionIconsByWhat'     => [
        'expenses'   => 'fa-long-arrow-left',
        'withdrawal' => 'fa-long-arrow-left',
        'revenue'    => 'fa-long-arrow-right',
        'deposit'    => 'fa-long-arrow-right',
        'transfer'   => 'fa-exchange',
        'transfers'  => 'fa-exchange',

    ],
    'bindables'                  => [
        // models
        'account'           => \FireflyIII\Models\Account::class,
        'attachment'        => \FireflyIII\Models\Attachment::class,
        'bill'              => \FireflyIII\Models\Bill::class,
        'budget'            => \FireflyIII\Models\Budget::class,
        'budgetLimit'       => \FireflyIII\Models\BudgetLimit::class,
        'category'          => \FireflyIII\Models\Category::class,
        'linkType'          => \FireflyIII\Models\LinkType::class,
        'transactionType'   => \FireflyIII\Models\TransactionType::class,
        'journalLink'       => \FireflyIII\Models\TransactionJournalLink::class,
        'currency'          => \FireflyIII\Models\TransactionCurrency::class,
        'piggyBank'         => \FireflyIII\Models\PiggyBank::class,
        'tj'                => \FireflyIII\Models\TransactionJournal::class,
        'tag'               => \FireflyIII\Models\Tag::class,
        'rule'              => \FireflyIII\Models\Rule::class,
        'ruleGroup'         => \FireflyIII\Models\RuleGroup::class,
        'exportJob'         => \FireflyIII\Models\ExportJob::class,
        'importJob'         => \FireflyIII\Models\ImportJob::class,
        'transaction'       => \FireflyIII\Models\Transaction::class,
        'user'              => \FireflyIII\User::class,

        // strings

        // dates
        'start_date'        => \FireflyIII\Support\Binder\Date::class,
        'end_date'          => \FireflyIII\Support\Binder\Date::class,
        'date'              => \FireflyIII\Support\Binder\Date::class,

        // lists
        'accountList'       => \FireflyIII\Support\Binder\AccountList::class,
        'expenseList'       => \FireflyIII\Support\Binder\AccountList::class,
        'budgetList'        => \FireflyIII\Support\Binder\BudgetList::class,
        'journalList'       => \FireflyIII\Support\Binder\JournalList::class,
        'categoryList'      => \FireflyIII\Support\Binder\CategoryList::class,
        'tagList'           => \FireflyIII\Support\Binder\TagList::class,

        // others
        'fromCurrencyCode'  => \FireflyIII\Support\Binder\CurrencyCode::class,
        'toCurrencyCode'    => \FireflyIII\Support\Binder\CurrencyCode::class,
        'unfinishedJournal' => \FireflyIII\Support\Binder\UnfinishedJournal::class,


    ],
    'rule-triggers'              => [
        'user_action'           => 'FireflyIII\TransactionRules\Triggers\UserAction',
        'from_account_starts'   => 'FireflyIII\TransactionRules\Triggers\FromAccountStarts',
        'from_account_ends'     => 'FireflyIII\TransactionRules\Triggers\FromAccountEnds',
        'from_account_is'       => 'FireflyIII\TransactionRules\Triggers\FromAccountIs',
        'from_account_contains' => 'FireflyIII\TransactionRules\Triggers\FromAccountContains',
        'to_account_starts'     => 'FireflyIII\TransactionRules\Triggers\ToAccountStarts',
        'to_account_ends'       => 'FireflyIII\TransactionRules\Triggers\ToAccountEnds',
        'to_account_is'         => 'FireflyIII\TransactionRules\Triggers\ToAccountIs',
        'to_account_contains'   => 'FireflyIII\TransactionRules\Triggers\ToAccountContains',
        'amount_less'           => 'FireflyIII\TransactionRules\Triggers\AmountLess',
        'amount_exactly'        => 'FireflyIII\TransactionRules\Triggers\AmountExactly',
        'amount_more'           => 'FireflyIII\TransactionRules\Triggers\AmountMore',
        'description_starts'    => 'FireflyIII\TransactionRules\Triggers\DescriptionStarts',
        'description_ends'      => 'FireflyIII\TransactionRules\Triggers\DescriptionEnds',
        'description_contains'  => 'FireflyIII\TransactionRules\Triggers\DescriptionContains',
        'description_is'        => 'FireflyIII\TransactionRules\Triggers\DescriptionIs',
        'transaction_type'      => 'FireflyIII\TransactionRules\Triggers\TransactionType',
        'category_is'           => 'FireflyIII\TransactionRules\Triggers\CategoryIs',
        'budget_is'             => 'FireflyIII\TransactionRules\Triggers\BudgetIs',
        'tag_is'                => 'FireflyIII\TransactionRules\Triggers\TagIs',
        'has_attachments'       => 'FireflyIII\TransactionRules\Triggers\HasAttachment',
        'has_no_category'       => 'FireflyIII\TransactionRules\Triggers\HasNoCategory',
        'has_any_category'      => 'FireflyIII\TransactionRules\Triggers\HasAnyCategory',
        'has_no_budget'         => 'FireflyIII\TransactionRules\Triggers\HasNoBudget',
        'has_any_budget'        => 'FireflyIII\TransactionRules\Triggers\HasAnyBudget',
        'has_no_tag'            => 'FireflyIII\TransactionRules\Triggers\HasNoTag',
        'has_any_tag'           => 'FireflyIII\TransactionRules\Triggers\HasAnyTag',
        'notes_contain'         => 'FireflyIII\TransactionRules\Triggers\NotesContain',
        'notes_start'           => 'FireflyIII\TransactionRules\Triggers\NotesStart',
        'notes_end'             => 'FireflyIII\TransactionRules\Triggers\NotesEnd',
        'notes_are'             => 'FireflyIII\TransactionRules\Triggers\NotesAre',
        'no_notes'              => 'FireflyIII\TransactionRules\Triggers\NotesEmpty',
        'any_notes'             => 'FireflyIII\TransactionRules\Triggers\NotesAny',
    ],
    'rule-actions'               => [
        'set_category'            => 'FireflyIII\TransactionRules\Actions\SetCategory',
        'clear_category'          => 'FireflyIII\TransactionRules\Actions\ClearCategory',
        'set_budget'              => 'FireflyIII\TransactionRules\Actions\SetBudget',
        'clear_budget'            => 'FireflyIII\TransactionRules\Actions\ClearBudget',
        'add_tag'                 => 'FireflyIII\TransactionRules\Actions\AddTag',
        'remove_tag'              => 'FireflyIII\TransactionRules\Actions\RemoveTag',
        'remove_all_tags'         => 'FireflyIII\TransactionRules\Actions\RemoveAllTags',
        'set_description'         => 'FireflyIII\TransactionRules\Actions\SetDescription',
        'append_description'      => 'FireflyIII\TransactionRules\Actions\AppendDescription',
        'prepend_description'     => 'FireflyIII\TransactionRules\Actions\PrependDescription',
        'set_source_account'      => 'FireflyIII\TransactionRules\Actions\SetSourceAccount',
        'set_destination_account' => 'FireflyIII\TransactionRules\Actions\SetDestinationAccount',
        'set_notes'               => 'FireflyIII\TransactionRules\Actions\SetNotes',
        'append_notes'            => 'FireflyIII\TransactionRules\Actions\AppendNotes',
        'prepend_notes'           => 'FireflyIII\TransactionRules\Actions\PrependNotes',
        'clear_notes'             => 'FireflyIII\TransactionRules\Actions\ClearNotes',
    ],
    'rule-actions-text'          => [
        'set_category',
        'set_budget',
        'add_tag',
        'remove_tag',
        'set_description',
        'append_description',
        'prepend_description',
    ],
    'test-triggers'              => [
        'limit' => 10,
        'range' => 200,
    ],
    'default_currency'           => 'EUR',
    'default_language'           => 'en_US',
    'search_modifiers'           => ['amount_is', 'amount', 'amount_max', 'amount_min', 'amount_less', 'amount_more', 'source', 'destination', 'category',
                                     'budget', 'bill', 'type', 'date', 'date_before', 'date_after', 'on', 'before', 'after'],
    // tag notes has_attachments
    'currency_exchange_services' => [
        'fixerio' => 'FireflyIII\Services\Currency\FixerIO',
    ],
    'preferred_exchange_service' => 'fixerio',

];
