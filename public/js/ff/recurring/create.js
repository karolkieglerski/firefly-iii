/*
 * create.js
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

/** global: Modernizr, currencies */

$(document).ready(function () {
    "use strict";
    if (!Modernizr.inputtypes.date) {
        $('input[type="date"]').datepicker(
            {
                dateFormat: 'yy-mm-dd'
            }
        );
    }
    initializeButtons();
    initializeAutoComplete();
    respondToFirstDateChange();
    $('.switch-button').on('click', switchTransactionType);
    $('#ffInput_first_date').on('change', respondToFirstDateChange);


});

function respondToFirstDateChange() {
    var obj = $('#ffInput_first_date');
    var select = $('#ffInput_repetition_type');
    var date = obj.val();
    select.prop('disabled', true);
    $.getJSON(suggestUri, {date: date}).fail(function () {
        console.error('Could not load repetition suggestions');
        alert('Could not load repetition suggestions');
    }).done(parseRepetitionSuggestions);
}

function parseRepetitionSuggestions(data) {

    var select = $('#ffInput_repetition_type');
    select.empty();
    for (var k in data) {
        if (data.hasOwnProperty(k)) {
            select.append($('<option>').val(k).attr('label', data[k]).text(data[k]));
        }
    }
    select.removeAttr('disabled');
}

function initializeAutoComplete() {
    // auto complete things:
    $.getJSON('json/tags').done(function (data) {
        var opt = {
            typeahead: {
                source: data,
                afterSelect: function () {
                    this.$element.val("");
                },
                autoSelect: false,
            },
            autoSelect: false,
        };

        $('input[name="tags"]').tagsinput(
            opt
        );
    });

    if ($('input[name="destination_account_name"]').length > 0) {
        $.getJSON('json/expense-accounts').done(function (data) {
            $('input[name="destination_account_name"]').typeahead({source: data, autoSelect: false});
        });
    }

    if ($('input[name="source_account_name"]').length > 0) {
        $.getJSON('json/revenue-accounts').done(function (data) {
            $('input[name="source_account_name"]').typeahead({source: data, autoSelect: false});
        });
    }

    $.getJSON('json/categories').done(function (data) {
        $('input[name="category"]').typeahead({source: data, autoSelect: false});
    });
}

/**
 *
 * @param e
 */
function switchTransactionType(e) {
    var target = $(e.target);
    transactionType = target.data('value');
    initializeButtons();
    return false;
}

/**
 * Loop the three buttons and do some magic.
 */
function initializeButtons() {
    console.log('Now in initializeButtons()');
    $.each($('.switch-button'), function (i, v) {
        var btn = $(v);
        console.log('Value is ' + btn.data('value'));
        if (btn.data('value') === transactionType) {
            btn.addClass('btn-info disabled').removeClass('btn-default');
        } else {
            btn.removeClass('btn-info disabled').addClass('btn-default');
        }
    });
    updateFormFields();
}

/**
 * Hide and/or show stuff when switching:
 */
function updateFormFields() {

    if (transactionType === 'withdrawal') {
        // hide source account name:
        $('#source_account_name_holder').hide();

        // show source account ID:
        $('#source_account_id_holder').show();

        // show destination name:
        $('#destination_account_name_holder').show();

        // hide destination ID:
        $('#destination_account_id_holder').hide();

        // show budget
        $('#budget_id_holder').show();

        // hide piggy bank:
        $('#piggy_bank_id_holder').hide();
    }

    if (transactionType === 'deposit') {
        $('#source_account_name_holder').show();
        $('#source_account_id_holder').hide();
        $('#destination_account_name_holder').hide();
        $('#destination_account_id_holder').show();
        $('#budget_id_holder').hide();
        $('#piggy_bank_id_holder').hide();
    }

    if (transactionType === 'transfer') {
        $('#source_account_name_holder').hide();
        $('#source_account_id_holder').show();
        $('#destination_account_name_holder').hide();
        $('#destination_account_id_holder').show();
        $('#budget_id_holder').hide();
        $('#piggy_bank_id_holder').show();
    }
}
