/*
 * reconcile.js
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

/** global: overviewUri, transactionsUri, indexUri,accounting */

var balanceDifference = 0;
var difference = 0;
var selectedAmount = 0;
var reconcileStarted = false;
var changedBalances = false;

/**
 *
 */
$(function () {
    "use strict";

    /*
    Respond to changes in balance statements.
     */
    $('input[type="number"]').on('change', function () {
        console.log('On change number input.');
        if (reconcileStarted) {
            calculateBalanceDifference();
            difference = balanceDifference - selectedAmount;
            updateDifference();

        }
        changedBalances = true;
    });

    /*
    Respond to changes in the date range.
     */
    $('input[type="date"]').on('change', function () {
        console.log('On change date input.');
        if (reconcileStarted) {
            // hide original instructions.
            $('.select_transactions_instruction').hide();

            // show date-change warning
            $('.date_change_warning').show();

            // show update button
            $('.change_date_button').show();
        }
    });

    $('.change_date_button').click(startReconcile);
    $('.start_reconcile').click(startReconcile);
    $('.store_reconcile').click(storeReconcile);

});

function storeReconcile() {
    console.log('In storeReconcile.');
    // get modal HTML:
    var ids = [];
    $.each($('.reconcile_checkbox:checked'), function (i, v) {
        ids.push($(v).data('id'));
    });
    console.log('Ids is ' + ids);
    var cleared = [];
    $.each($('input[class="cleared"]'), function (i, v) {
        var obj = $(v);
        cleared.push(obj.data('id'));
    });
    console.log('Cleared is ' + ids);

    var variables = {
        startBalance: parseFloat($('input[name="start_balance"]').val()),
        endBalance: parseFloat($('input[name="end_balance"]').val()),
        startDate: $('input[name="start_date"]').val(),
        startEnd: $('input[name="end_date"]').val(),
        transactions: ids,
        cleared: cleared,
    };
    console.log
    var uri = overviewUri.replace('%start%', $('input[name="start_date"]').val()).replace('%end%', $('input[name="end_date"]').val());


    $.getJSON(uri, variables).done(function (data) {
        $('#defaultModal').empty().html(data.html).modal('show');
    });
}

/**
 * What happens when you check a checkbox:
 * @param e
 */
function checkReconciledBox(e) {
    console.log('In checkReconciledBox.');
    var el = $(e.target);
    var amount = parseFloat(el.val());
    // if checked, add to selected amount
    if (el.prop('checked') === true && el.data('younger') === false) {
        selectedAmount = selectedAmount - amount;
    }
    if (el.prop('checked') === false && el.data('younger') === false) {
        selectedAmount = selectedAmount + amount;
    }
    difference = balanceDifference - selectedAmount;
    updateDifference();
}


/**
 * Calculate the difference between given start and end balance
 * and put it in balanceDifference.
 */
function calculateBalanceDifference() {
    console.log('In calculateBalanceDifference.');
    var startBalance = parseFloat($('input[name="start_balance"]').val());
    var endBalance = parseFloat($('input[name="end_balance"]').val());
    balanceDifference = startBalance - endBalance;
    //if (balanceDifference < 0) {
    //  balanceDifference = balanceDifference * -1;
    //}
}

/**
 * Grab all transactions, update the URL and place the set of transactions in the box.
 * This more or less resets the reconciliation.
 */
function getTransactionsForRange() {
    console.log('In getTransactionsForRange.');
    // clear out the box:
    $('#transactions_holder').empty().append($('<p>').addClass('text-center').html('<i class="fa fa-fw fa-spin fa-spinner"></i>'));
    var uri = transactionsUri.replace('%start%', $('input[name="start_date"]').val()).replace('%end%', $('input[name="end_date"]').val());
    var index = indexUri.replace('%start%', $('input[name="start_date"]').val()).replace('%end%', $('input[name="end_date"]').val());
    window.history.pushState('object or string', "Reconcile account", index);

    $.getJSON(uri).done(placeTransactions);
}

/**
 * Loop over all transactions that have already been cleared (in the range) and add this to the selectedAmount.
 *
 */
function includeClearedTransactions() {
    console.log('In includeClearedTransactions.');
    $.each($('input[class="cleared"]'), function (i, v) {
        var obj = $(v);
        if (obj.data('younger') === false) {
            selectedAmount = selectedAmount - parseFloat(obj.val());
        }
    });
}

/**
 * Place the HTML for the transactions within the date range and update the balance difference.
 * @param data
 */
function placeTransactions(data) {
    console.log('In placeTransactions.');
    $('#transactions_holder').empty().html(data.html);
    selectedAmount = 0;
    // update start + end balance when user has not touched them.
    if (!changedBalances) {
        $('input[name="start_balance"]').val(data.startBalance);
        $('input[name="end_balance"]').val(data.endBalance);
    }

    // as long as the dates are equal, changing the balance does not matter.
    calculateBalanceDifference();

    // any already cleared transactions must be added to / removed from selectedAmount.
    includeClearedTransactions();

    difference = balanceDifference - selectedAmount;
    updateDifference();

    // enable the check buttons:
    $('.reconcile_checkbox').prop('disabled', false).unbind('change').change(checkReconciledBox);

    // show the other instruction:
    $('.select_transactions_instruction').show();

    $('.store_reconcile').prop('disabled', false);
}

/**
 *
 * @returns {boolean}
 */
function startReconcile() {
    console.log('In startReconcile.');
    reconcileStarted = true;

    // hide the start button.
    $('.start_reconcile').hide();

    // hide the start instructions:
    $('.update_balance_instruction').hide();

    // hide date-change warning
    $('.date_change_warning').hide();

    // hide update button
    $('.change_date_button').hide();

    getTransactionsForRange();


    return false;
}

function updateDifference() {
    console.log('In updateDifference.');
    var addClass = 'text-info';
    if (difference > 0) {
        addClass = 'text-success';
    }
    if (difference < 0) {
        addClass = 'text-danger';
    }
    $('#difference').addClass(addClass).text(accounting.formatMoney(difference));
}