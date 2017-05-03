<?php
/**
 * csv.php
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

return [

    'import_configure_title' => 'Nastavitve uvoza',
    'import_configure_intro' => 'Tu je nekaj nastavitev za uvoz CSV datoteke. Prosim, označite, če vaša CSV datoteka vsebuje prvo vrstico z naslovi stolpcev in v kakšnem formatu so izpisani datumi. Morda bo to zahtevalo nekaj poizkušanja. Ločilo v CSV datoteki je ponavadi ",", lahko pa je tudi ";". Pozorno preverite.',
    'import_configure_form'  => 'Osnovne možnosti za uvoz CSV datoteke.',
    'header_help'            => 'Preverite ali prva vrstica v CSV datoteki vsebuje naslove stolpcev.',
    'date_help'              => 'Formatiranje datuma in časa v vaši CSV datoteki. Uporabite obliko zapisa kot je navedena<a href="https://secure.php.net/manual/en/datetime.createfromformat.php#refsect1-datetime.createfromformat-parameters"> na tej strani</a>. Privzeta vrednost bo prepoznala datume, ki so videti takole:: dateExample.',
    'delimiter_help'         => 'Izberi ločilo, ki je uporabljeno za ločevanje med posameznimi stolpci v vaši datoteki. Če niste prepričani, je vejica najbolj pogosta izbira.',
    'import_account_help'    => 'Če vaša CSV datoteka ne vsebuje informacij o vaših premoženjskih računih, uporabite ta seznam, da izberete kateremu računu pripadajo transakcije v CSV datoteki.',
    'upload_not_writeable'   => 'The grey box contains a file path. It should be writeable. Please make sure it is.',

    // roles
    'column_roles_title'     => 'doličite pomen stolpcev',
    'column_roles_table'     => 'tabela',
    'column_name'            => 'Ime stolpca',
    'column_example'         => 'primeri podatkov',
    'column_role'            => 'pomen podatkov v stolpcu',
    'do_map_value'           => 'poveži te vrednosti',
    'column'                 => 'stolpec',
    'no_example_data'        => 'primeri podatkov niso na voljo',
    'store_column_roles'     => 'nadaljuj z uvozom',
    'do_not_map'             => '(ne poveži)',
    'map_title'              => 'poveži podatke za uvoz s podatki iz Firefly III',
    'map_text'               => 'In the following tables, the left value shows you information found in your uploaded CSV file. It is your task to map this value, if possible, to a value already present in your database. Firefly will stick to this mapping. If there is no value to map to, or you do not wish to map the specific value, select nothing.',

    'field_value'          => 'Field value',
    'field_mapped_to'      => 'Mapped to',
    'store_column_mapping' => 'shrani nastavitve',

    // map things.


    'column__ignore'                => '(ignoriraj ta stolpec)',
    'column_account-iban'           => 'premoženjski račun (IBAN)',
    'column_account-id'             => 'ID premoženjskega računa (Firefly)',
    'column_account-name'           => 'premoženjski račun (ime)',
    'column_amount'                 => 'znesek',
    'column_amount-comma-separated' => 'znesek (z decimalno vejico)',
    'column_bill-id'                => 'Bill ID (matching Firefly)',
    'column_bill-name'              => 'Bill name',
    'column_budget-id'              => 'ID bugžeta (Firefly)',
    'column_budget-name'            => 'ime budžeta',
    'column_category-id'            => 'ID Kategorije (Firefly)',
    'column_category-name'          => 'ime kategorije',
    'column_currency-code'          => 'koda valute (ISO 4217)',
    'column_currency-id'            => 'ID valute (Firefly)',
    'column_currency-name'          => 'ime valute (Firefly)',
    'column_currency-symbol'        => 'simbol valute (Firefly)',
    'column_date-interest'          => 'Interest calculation date',
    'column_date-book'              => 'datum knjiženja transakcije',
    'column_date-process'           => 'datum izvedbe transakcije',
    'column_date-transaction'       => 'datum',
    'column_description'            => 'opis',
    'column_opposing-iban'          => 'ciljni račun (IBAN)',
    'column_opposing-id'            => 'protiračun (firefly)',
    'column_external-id'            => 'zunanja ID številka',
    'column_opposing-name'          => 'ime ciljnega računa',
    'column_rabo-debet-credit'      => 'Rabobank specific debet/credit indicator',
    'column_ing-debet-credit'       => 'ING specific debet/credit indicator',
    'column_sepa-ct-id'             => 'SEPA Credit Transfer end-to-end ID',
    'column_sepa-ct-op'             => 'SEPA Credit Transfer opposing account',
    'column_sepa-db'                => 'SEPA Direct Debet',
    'column_tags-comma'             => 'značke (ločene z vejicami)',
    'column_tags-space'             => 'značke (ločene s presledki)',
    'column_account-number'         => 'premoženjski račun (številka računa)',
    'column_opposing-number'        => 'protiračun (številka računa)',
];