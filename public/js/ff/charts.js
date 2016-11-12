/*
 * charts.js
 * Copyright (C) 2016 thegrumpydictator@gmail.com
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

/* globals $, Chart, currencySymbol,mon_decimal_point ,accounting, mon_thousands_sep, frac_digits */

var allCharts = {};

/*
 Make some colours:
 */
var colourSet = [
    [53, 124, 165],
    [0, 141, 76],
    [219, 139, 11],
    [202, 25, 90],
    [85, 82, 153],
    [66, 133, 244],
    [219, 68, 55],
    [244, 180, 0],
    [15, 157, 88],
    [171, 71, 188],
    [0, 172, 193],
    [255, 112, 67],
    [158, 157, 36],
    [92, 107, 192],
    [240, 98, 146],
    [0, 121, 107],
    [194, 24, 91]
];

var fillColors = [];
var strokePointHighColors = [];


for (var i = 0; i < colourSet.length; i++) {
    fillColors.push("rgba(" + colourSet[i][0] + ", " + colourSet[i][1] + ", " + colourSet[i][2] + ", 0.2)");
    strokePointHighColors.push("rgba(" + colourSet[i][0] + ", " + colourSet[i][1] + ", " + colourSet[i][2] + ", 0.9)");
}

Chart.defaults.global.legend.display = false;
Chart.defaults.global.animation.duration = 0;
Chart.defaults.global.responsive = true;
Chart.defaults.global.maintainAspectRatio = false;

/*
 Set default options:
 */
var defaultAreaOptions = {
    scales: {
        xAxes: [
            {
                gridLines: {
                    display: false
                }
            }
        ],
        yAxes: [{
            display: true,
            ticks: {
                callback: function (tickValue, index, ticks) {
                    "use strict";
                    return accounting.formatMoney(tickValue);

                }
            }
        }]
    },
    tooltips: {
        mode: 'label',
        callbacks: {
            label: function (tooltipItem, data) {
                "use strict";
                return data.datasets[tooltipItem.datasetIndex].label + ': ' + accounting.formatMoney(tooltipItem.yLabel);
            }
        }
    }
};


var defaultPieOptions = {
    tooltips: {
        callbacks: {
            label: function (tooltipItem, data) {
                "use strict";
                var value = data.datasets[0].data[tooltipItem.index];
                return data.labels[tooltipItem.index] + ': ' + accounting.formatMoney(value);
            }
        }
    },
    maintainAspectRatio: true,
    responsive: true
};


var defaultLineOptions = {
    scales: {
        xAxes: [
            {
                gridLines: {
                    display: false
                }
            }
        ],
        yAxes: [{
            display: true,
            ticks: {
                callback: function (tickValue, index, ticks) {
                    "use strict";
                    return accounting.formatMoney(tickValue);

                }
            }
        }]
    },
    tooltips: {
        mode: 'label',
        callbacks: {
            label: function (tooltipItem, data) {
                "use strict";
                return data.datasets[tooltipItem.datasetIndex].label + ': ' + accounting.formatMoney(tooltipItem.yLabel);
            }
        }
    }
};

var defaultColumnOptions = {
    scales: {
        xAxes: [
            {
                gridLines: {
                    display: false
                }
            }
        ],
        yAxes: [{
            ticks: {
                callback: function (tickValue, index, ticks) {
                    "use strict";
                    return accounting.formatMoney(tickValue);
                },
                beginAtZero: true
            }
        }]
    },
    elements: {
        line: {
            fill: false
        }
    },
    tooltips: {
        mode: 'label',
        callbacks: {
            label: function (tooltipItem, data) {
                "use strict";
                return data.datasets[tooltipItem.datasetIndex].label + ': ' + accounting.formatMoney(tooltipItem.yLabel);
            }
        }
    }
};

var defaultStackedColumnOptions = {
    stacked: true,
    scales: {
        xAxes: [{
            stacked: true,
            gridLines: {
                display: false
            }
        }],
        yAxes: [{
            stacked: true,
            ticks: {
                callback: function (tickValue, index, ticks) {
                    "use strict";
                    return accounting.formatMoney(tickValue);

                }
            }
        }]
    },
    tooltips: {
        mode: 'label',
        callbacks: {
            label: function (tooltipItem, data) {
                "use strict";
                return data.datasets[tooltipItem.datasetIndex].label + ': ' + accounting.formatMoney(tooltipItem.yLabel);
            }
        }
    }
};

/**
 * Function to draw a line chart:
 * @param URL
 * @param container
 * @param options
 */
function lineChart(URL, container, options) {
    "use strict";
    $.getJSON(URL).done(function (data) {

        var ctx = document.getElementById(container).getContext("2d");
        var newData = {};
        newData.datasets = [];

        for (var i = 0; i < data.count; i++) {
            newData.labels = data.labels;
            var dataset = data.datasets[i];
            dataset.backgroundColor = fillColors[i];
            newData.datasets.push(dataset);
        }

        new Chart(ctx, {
            type: 'line',
            data: data,
            options: defaultLineOptions
        });

    }).fail(function () {
        $('#' + container).addClass('general-chart-error');
    });
    console.log('URL for line chart : ' + URL);
}

/**
 * Function to draw an area chart:
 *
 * @param URL
 * @param container
 * @param options
 */
function areaChart(URL, container, options) {
    "use strict";

    if ($('#' + container).length === 0) {
        console.log('No container called ' + container + ' was found.');
        return;
    }

    $.getJSON(URL).done(function (data) {
        var ctx = document.getElementById(container).getContext("2d");
        var newData = {};
        newData.datasets = [];

        for (var i = 0; i < data.count; i++) {
            newData.labels = data.labels;
            var dataset = data.datasets[i];
            dataset.backgroundColor = fillColors[i];
            newData.datasets.push(dataset);
        }

        new Chart(ctx, {
            type: 'line',
            data: newData,
            options: defaultAreaOptions
        });

    }).fail(function () {
        $('#' + container).addClass('general-chart-error');
    });

    console.log('URL for area chart: ' + URL);
}

/**
 *
 * @param URL
 * @param container
 * @param options
 */
function columnChart(URL, container, options) {
    "use strict";

    options = options || {};

    $.getJSON(URL).done(function (data) {

        var result = true;
        if (options.beforeDraw) {
            result = options.beforeDraw(data, {url: URL, container: container});
        }
        if (result === false) {
            return;
        }
        console.log('Will draw columnChart(' + URL + ')');

        var ctx = document.getElementById(container).getContext("2d");
        var newData = {};
        newData.datasets = [];

        for (var i = 0; i < data.count; i++) {
            newData.labels = data.labels;
            var dataset = data.datasets[i];
            dataset.backgroundColor = fillColors[i];
            newData.datasets.push(dataset);
        }
        new Chart(ctx, {
            type: 'bar',
            data: data,
            options: defaultColumnOptions
        });

    }).fail(function () {
        $('#' + container).addClass('general-chart-error');
    });
    console.log('URL for column chart : ' + URL);
}

/**
 *
 * @param URL
 * @param container
 * @param options
 */
function stackedColumnChart(URL, container, options) {
    "use strict";

    options = options || {};


    $.getJSON(URL).done(function (data) {

        var result = true;
        if (options.beforeDraw) {
            result = options.beforeDraw(data, {url: URL, container: container});
        }
        if (result === false) {
            return;
        }


        var ctx = document.getElementById(container).getContext("2d");
        var newData = {};
        newData.datasets = [];

        for (var i = 0; i < data.count; i++) {
            newData.labels = data.labels;
            var dataset = data.datasets[i];
            dataset.backgroundColor = fillColors[i];
            newData.datasets.push(dataset);
        }
        new Chart(ctx, {
            type: 'bar',
            data: data,
            options: defaultStackedColumnOptions
        });


    }).fail(function () {
        $('#' + container).addClass('general-chart-error');
    });
    console.log('URL for stacked column chart : ' + URL);
}

/**
 *
 * @param URL
 * @param container
 * @param options
 */
function pieChart(URL, container, options) {
    "use strict";

    if ($('#' + container).length === 0) {
        console.log('No container called ' + container + ' was found.');
        return;
    }

    $.getJSON(URL).done(function (data) {

        if (allCharts.hasOwnProperty(container)) {
            console.log('Will draw updated pie chart');

            allCharts[container].data.datasets = data.datasets;
            allCharts[container].data.labels = data.labels;
            allCharts[container].update();
        } else {
            // new chart!
            console.log('Will draw new pie chart');
            var ctx = document.getElementById(container).getContext("2d");
            allCharts[container] = new Chart(ctx, {
                type: 'pie',
                data: data,
                options: defaultPieOptions
            });
        }


    }).fail(function () {
        $('#' + container).addClass('general-chart-error');
    });


    console.log('URL for pie chart : ' + URL);

}
