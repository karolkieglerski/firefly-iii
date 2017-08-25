<?php
declare(strict_types=1);

/**
 * intro.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 * This software may be modified and distributed under the terms of the Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

return [
    // index
    'index_intro'                           => 'Welkom op de homepage van Firefly III. Neem even de tijd voor deze introductie zodat je Firefly III leert kennen.',
    'index_accounts-chart'                  => 'Deze grafiek toont het saldo van je betaalrekening(en). Welke rekeningen zichtbaar zijn kan je aangeven bij de instellingen.',
    'index_box_out_holder'                  => 'Dit vakje en de vakjes er naast geven een snel overzicht van je financiële situatie.',
    'index_help'                            => 'Als je ooit hulp nodig hebt, klik dan hier.',
    'index_outro'                           => 'De meeste pagina\'s in Firefly III beginnen met een kleine rondleiding zoals deze. Zoek me op als je vragen of commentaar hebt. Veel plezier!',
    'index_sidebar-toggle'                  => 'Nieuwe transacties, rekeningen en andere dingen maak je met het menu onder deze knop.',

    // create account:
    'accounts_create_iban'                  => 'Geef je rekeningen een geldige IBAN. Dat scheelt met importeren van data.',
    'accounts_create_asset_opening_balance' => 'Betaalrekeningen kunnen een startsaldo hebben, waarmee het begin van deze rekening in Firefly wordt aangegeven.',
    'accounts_create_asset_currency'        => 'Firefly III ondersteunt meerdere valuta. Hier stel je de valuta in van je betaalrekening.',
    'accounts_create_asset_virtual'         => 'Soms is het handig om je betaalrekening een virtueel saldo te geven: een extra bedrag dat altijd bij het daadwerkelijke saldo wordt opgeteld.',

    // budgets index
    'budgets_index_intro'                   => 'Budgetten worden gebruikt om je financiën te beheren en vormen een van de kernfuncties van Firefly III.',
    'budgets_index_set_budget'              => 'Stel je totale budget voor elke periode in, zodat Firefly je kan vertellen of je alle beschikbare geld hebt gebudgetteerd.',
    'budgets_index_see_expenses_bar'        => 'Het besteden van geld zal deze balk langzaam vullen.',
    'budgets_index_navigate_periods'        => 'Navigeer door periodes heen om je budget vooraf te bepalen.',
    'budgets_index_new_budget'              => 'Maak nieuwe budgetten naar wens.',
    'budgets_index_list_of_budgets'         => 'Gebruik deze tabel om de bedragen voor elk budget vast te stellen en te zien hoe je er voor staat.',

    // reports (index)
    'reports_index_intro'                   => 'Gebruik deze rapporten om gedetailleerde inzicht in je financiën te krijgen.',
    'reports_index_inputReportType'         => 'Kies een rapporttype. Bekijk de helppagina\'s om te zien wat elk rapport laat zien.',
    'reports_index_inputAccountsSelect'     => 'Je kunt naar keuze betaalrekeningen meenemen (of niet).',
    'reports_index_inputDateRange'          => 'Kies zelf een datumbereik: van een dag tot tien jaar.',
    'reports_index_extra-options-box'       => 'Sommige rapporten bieden extra filters en opties. Kies een rapporttype en kijk of hier iets verandert.',

    // reports (reports)
    'reports_report_default_intro'          => 'Dit rapport geeft je een snel en uitgebreid overzicht van je financiën. Laat het me weten als je hier dingen mist!',
    'reports_report_audit_intro'            => 'Dit rapport geeft je gedetailleerde inzichten in je betaalrekeningen.',
    'reports_report_audit_optionsBox'       => 'Gebruik deze vinkjes om voor jou interessante kolommen te laten zien of te verbergen.',

    'reports_report_category_intro'                  => 'Dit rapport geeft je inzicht in één of meerdere categorieën.',
    'reports_report_category_pieCharts'              => 'Deze grafieken geven je inzicht in de uitgaven en inkomsten per categorie of per rekening.',
    'reports_report_category_incomeAndExpensesChart' => 'Deze grafiek toont je uitgaven en inkomsten per categorie.',

    'reports_report_tag_intro'                  => 'Dit rapport geeft je inzicht in één of meerdere tags.',
    'reports_report_tag_pieCharts'              => 'Deze grafieken geven je inzicht in de uitgaven en inkomsten per tag, rekening, categorie of budget.',
    'reports_report_tag_incomeAndExpensesChart' => 'Deze grafiek toont je uitgaven en inkomsten per tag.',

    'reports_report_budget_intro'                             => 'Dit rapport geeft je inzicht in één of meerdere budgetten.',
    'reports_report_budget_pieCharts'                         => 'Deze grafieken geven je inzicht in de uitgaven en inkomsten per budget of per rekening.',
    'reports_report_budget_incomeAndExpensesChart'            => 'Deze grafiek toont je uitgaven per budget.',

    // create transaction
    'transactions_create_switch_box'                          => 'Gebruik deze knoppen om snel van transactietype te wisselen.',
    'transactions_create_ffInput_category'                    => 'Je kan in dit veld vrij typen. Eerder gemaakte categorieën komen als suggestie naar boven.',
    'transactions_create_withdrawal_ffInput_budget'           => 'Link je uitgave aan een budget voor een beter financieel overzicht.',
    'transactions_create_withdrawal_currency_dropdown_amount' => 'Gebruik deze dropdown als je uitgave in een andere valuta is.',
    'transactions_create_deposit_currency_dropdown_amount'    => 'Gebruik deze dropdown als je inkomsten in een andere valuta zijn.',
    'transactions_create_transfer_ffInput_piggy_bank_id'      => 'Selecteer een spaarpotje en link deze overschrijving aan je spaargeld.',

    // piggy banks index:
    'piggy-banks_index_saved'                                 => 'Dit veld laat zien hoeveel geld er in elk spaarpotje zit.',
    'piggy-banks_index_button'                                => 'Naast deze balk zitten twee knoppen (+ en -) om geld aan je spaarpotje toe te voegen, of er uit te halen.',
    'piggy-banks_index_accountStatus'                         => 'Voor elke betaalrekening met minstens één spaarpotje zie je hier de status.',

    // create piggy
    'piggy-banks_create_name'                                 => 'Wat is je doel? Een nieuwe zithoek, een camera of geld voor noodgevallen?',
    'piggy-banks_create_date'                                 => 'Je kan een doeldatum of een deadline voor je spaarpot instellen.',

    // show piggy
    'piggy-banks_show_piggyChart'                             => 'Deze grafiek toont de geschiedenis van dit spaarpotje.',
    'piggy-banks_show_piggyDetails'                           => 'Enkele details over je spaarpotje',
    'piggy-banks_show_piggyEvents'                            => 'Eventuele stortingen (van en naar) worden hier ook vermeld.',

    // bill index
    'bills_index_paid_in_period'                              => 'Dit veld geeft aan wanneer het contract het laatst is betaald.',
    'bills_index_expected_in_period'                          => 'Dit veld geeft aan voor elk contract of en wanneer je hem weer moet betalen.',

    // show bill
    'bills_show_billInfo'                                     => 'Deze tabel bevat wat algemene informatie over dit contract.',
    'bills_show_billButtons'                                  => 'Gebruik deze knop om oude transacties opnieuw te scannen, zodat ze aan dit contract worden gekoppeld.',
    'bills_show_billChart'                                    => 'Deze grafiek toont de transacties gekoppeld aan dit contract.',

    // create bill
    'bills_create_name'                                       => 'Gebruik een beschrijvende naam zoals "huur" of "zorgverzekering".',
    'bills_create_match'                                      => 'Om transacties te koppelen gebruik je termen uit de transacties of de bijbehorende crediteur. Alle termen moeten overeen komen.',
    'bills_create_amount_min_holder'                          => 'Stel ook een minimum- en maximumbedrag in.',
    'bills_create_repeat_freq_holder'                         => 'Most bills repeat monthly, but you can set another frequency here.',
    'bills_create_skip_holder'                                => 'If a bill repeats every 2 weeks for example, the "skip"-field should be set to "1" to skip every other week.',

    // rules index
    'rules_index_intro'                                       => 'Firefly III allows you to manage rules, that will automagically be applied to any transaction you create or edit.',
    'rules_index_new_rule_group'                              => 'You can combine rules in groups for easier management.',
    'rules_index_new_rule'                                    => 'Maak zoveel regels als je wilt.',
    'rules_index_prio_buttons'                                => 'Order them any way you see fit.',
    'rules_index_test_buttons'                                => 'You can test your rules or apply them to existing transactions.',
    'rules_index_rule-triggers'                               => 'Rules have "triggers" and "actions" that you can order by drag-and-drop.',
    'rules_index_outro'                                       => 'Be sure to check out the help pages using the (?) icon in the top right!',

    // create rule:
    'rules_create_mandatory'                                  => 'Choose a descriptive title, and set when the rule should be fired.',
    'rules_create_ruletriggerholder'                          => 'Add as many triggers as you like, but remember that ALL triggers must match before any actions are fired.',
    'rules_create_test_rule_triggers'                         => 'Use this button to see which transactions would match your rule.',
    'rules_create_actions'                                    => 'Set as many actions as you like.',

    // preferences
    'preferences_index_tabs'                                  => 'Meer opties zijn beschikbaar achter deze tabbladen.',

    // currencies
    'currencies_index_intro'                                  => 'Firefly III supports multiple currencies, which you can change on this page.',
    'currencies_index_default'                                => 'Firefly III has one default currency. You can always switch of course using these buttons.',

    // create currency
    'currencies_create_code'                                  => 'This code should be ISO compliant (Google it for your new currency).',
];