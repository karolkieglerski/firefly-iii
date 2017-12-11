<?php
/**
 * csv.php
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
 * along with Firefly III.  If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

return [

    // initial config
    'initial_title'                 => 'Importer la configuration (1/3) - Configuration de l\'importation CSV basique',
    'initial_text'                  => 'Pour pouvoir importer votre fichier correctement, veuillez validez les options ci-dessous.',
    'initial_box'                   => 'Options d’importation CSV basique',
    'initial_box_title'             => 'Options d’importation CSV basique',
    'initial_header_help'           => 'Cochez cette case si la première ligne de votre fichier CSV contient les entêtes des colonnes.',
    'initial_date_help'             => 'Le format de la date et de l’heure dans votre fichier CSV. Utiliser le format comme indiqué sur <a href="https://secure.php.net/manual/en/datetime.createfromformat.php#refsect1-datetime.createfromformat-parameters">cette page</a>. La valeur par défaut va analyser les dates ressemblant à ceci : :dateExample.',
    'initial_delimiter_help'        => 'Choisissez le délimiteur de champ qui est utilisé dans votre fichier d’entrée. Si vous n’êtes pas certain, la virgule est l’option la plus sûre.',
    'initial_import_account_help'   => 'Si votre fichier CSV ne contient AUCUNE information concernant vos compte(s) actif, utilisez cette liste déroulante pour choisir à quel compte les opérations contenues dans le CSV font référence.',
    'initial_submit'                => 'Passez à l’étape 2/3',

    // new options:
    'apply_rules_title'             => 'Appliquer les règles',
    'apply_rules_description'       => 'Appliquer les règles. Notez que cela ralentit considérablement l\'import.',
    'match_bills_title'             => 'Faire correspondre les factures',
    'match_bills_description'       => 'Faire correspondre vos factures aux retraits nouvellement créés. Notez que cela ralentit considérablement l\'import.',

    // roles config
    'roles_title'                   => 'Importer la configuration (1/3) - Définir le rôle de chaque colonne',
    'roles_text'                    => 'Chaque colonne de votre fichier CSV contient certaines données. Veuillez indiquer quel type de données, l’importateur doit attendre. L’option de « mapper » les données signifie que vous allez lier chaque entrée trouvée dans la colonne à une valeur dans votre base de données. Souvent une colonne est la colonne contenant l’IBAN du compte opposé. Qui peut être facilement adapté aux IBAN déjà présents dans votre base de données.',
    'roles_table'                   => 'Tableau',
    'roles_column_name'             => 'Nom de colonne',
    'roles_column_example'          => 'Données d’exemple de colonne',
    'roles_column_role'             => 'Signification des données de colonne',
    'roles_do_map_value'            => 'Mapper ces valeurs',
    'roles_column'                  => 'Colonne',
    'roles_no_example_data'         => 'Pas de données disponibles',
    'roles_submit'                  => 'Passez à l’étape 3/3',
    'roles_warning'                 => 'La moindre des choses, c\'est de marquer une colonne comme colonne-montant. Il est conseillé de sélectionner également une colonne pour la description, la date et le compte opposé.',

    // map data
    'map_title'                     => 'Importer la configuration (3/3) - Connecter l\'importation des données aux données de Firefly III',
    'map_text'                      => 'Dans les tableaux suivants, la valeur gauche vous montre des informations trouvées dans votre fichier CSV téléchargé. C’est votre rôle de mapper cette valeur, si possible, une valeur déjà présente dans votre base de données. Firefly s’en tiendra à ce mappage. Si il n’y a pas de valeur correspondante, ou vous ne souhaitez pas la valeur spécifique de la carte, ne sélectionnez rien.',
    'map_field_value'               => 'Valeur du champ',
    'map_field_mapped_to'           => 'Mappé à',
    'map_do_not_map'                => '(ne pas mapper)',
    'map_submit'                    => 'Démarrer l\'importation',

    // map things.
    'column__ignore'                => '(ignorer cette colonne)',
    'column_account-iban'           => 'Compte d’actif (IBAN)',
    'column_account-id'             => 'Compte d\'actif (ID correspondant à Firefly)',
    'column_account-name'           => 'Compte d’actif (nom)',
    'column_amount'                 => 'Montant',
    'column_amount_debet'           => 'Montant (colonne débit)',
    'column_amount_credit'          => 'Montant (colonne de crédit)',
    'column_amount-comma-separated' => 'Montant (virgule comme séparateur décimal)',
    'column_bill-id'                => 'Facture (ID correspondant à Firefly)',
    'column_bill-name'              => 'Nom de la facture',
    'column_budget-id'              => 'Budget (ID correspondant à Firefly)',
    'column_budget-name'            => 'Nom du budget',
    'column_category-id'            => 'Catégorie (ID correspondant à Firefly)',
    'column_category-name'          => 'Nom de la catégorie',
    'column_currency-code'          => 'Code des monnaies (<a href="https://fr. wikipedia. org/wiki/ISO_4217">ISO 4217</a>)',
    'column_currency-id'            => 'Devise (ID correspondant à Firefly)',
    'column_currency-name'          => 'Nom de la devise (correspondant à Firefly)',
    'column_currency-symbol'        => 'Symbole de la devise (correspondant à Firefly)',
    'column_date-interest'          => 'Date de calcul des intérêts',
    'column_date-book'              => 'Date de valeur de la transaction',
    'column_date-process'           => 'Date de traitement de la transaction',
    'column_date-transaction'       => 'Date',
    'column_description'            => 'Description',
    'column_opposing-iban'          => 'Compte destination (IBAN)',
    'column_opposing-id'            => 'ID du Compte destination(ID correspondant Firefly)',
    'column_external-id'            => 'Identifiant externe',
    'column_opposing-name'          => 'Compte destination (nom)',
    'column_rabo-debet-credit'      => 'Indicateur spécifique débit/crédit à Rabobank',
    'column_ing-debet-credit'       => 'Indicateur spécifique débit/crédit à ING',
    'column_sepa-ct-id'             => 'SEPA Transfert Crédit ID de bout en bout',
    'column_sepa-ct-op'             => 'SEPA Transfert Crédit compte opposé',
    'column_sepa-db'                => 'SEPA débit immédiat',
    'column_tags-comma'             => 'Mots-clés (séparé par des virgules)',
    'column_tags-space'             => 'Mots-clés (séparé par des espaces)',
    'column_account-number'         => 'Compte d’actif (numéro de compte)',
    'column_opposing-number'        => 'Compte destination (numéro de compte)',
];
