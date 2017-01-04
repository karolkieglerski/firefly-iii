/*
 * month.js
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms of the
 * Creative Commons Attribution-ShareAlike 4.0 International License.
 *
 * See the LICENSE file for details.
 */

/** global: budgetExpenseUri, accountExpenseUri, mainUri */

$(function () {
    "use strict";
    drawChart();

    $('#budgets-out-pie-chart-checked').on('change', function () {
        redrawPieChart('budgets-out-pie-chart', budgetExpenseUri);
    });

    $('#accounts-out-pie-chart-checked').on('change', function () {
        redrawPieChart('accounts-out-pie-chart', accountExpenseUri);
    });

});


function drawChart() {
    "use strict";

    // month view:
    doubleYNonStackedChart(mainUri, 'in-out-chart');

    // draw pie chart of income, depending on "show other transactions too":
    redrawPieChart('budgets-out-pie-chart', budgetExpenseUri);
    redrawPieChart('accounts-out-pie-chart', accountExpenseUri);


}

function redrawPieChart(container, uri) {
    "use strict";
    var checkbox = $('#' + container + '-checked');

    var others = '0';
    // check if box is checked:
    if (checkbox.prop('checked')) {
        others = '1';
    }
    uri = uri.replace('OTHERS', others);

    pieChart(uri, container);

}
