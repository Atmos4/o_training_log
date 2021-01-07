//Disabled because poorly optimised
var customTooltip = function(tooltipModel) {
    // Tooltip Element
    var tooltipEl = document.getElementById('chartjs-tooltip');
    var hoverLine = document.getElementById('hover-line');

    // Create element on first render
    if (!tooltipEl) {
        tooltipEl = document.createElement('div');
        tooltipEl.id = 'chartjs-tooltip';
        tooltipEl.innerHTML = '<table></table>';
        document.body.appendChild(tooltipEl);
    }
    if (!hoverLine){
        hoverLine = document.createElement('div');
        hoverLine.id = 'hover-line';
        document.body.appendChild(hoverLine);
    }

    // Hide if no tooltip
    if (tooltipModel.opacity === 0) {
        tooltipEl.style.opacity = 0;
        hoverLine.style.opacity = 0;
        return;
    }

    // Set caret Position
    tooltipEl.classList.remove('above', 'below', 'no-transform');
    if (tooltipModel.yAlign) {
        tooltipEl.classList.add(tooltipModel.yAlign);
    } else {
        tooltipEl.classList.add('no-transform');
    }

    function getBody(bodyItem) {
        return bodyItem.lines;
    }

    // Set Text
    if (tooltipModel.body) {
        var titleLines = tooltipModel.title || [];
        var bodyLines = tooltipModel.body.map(getBody);

        var innerHtml = '<thead>';

        titleLines.forEach(function(title) {
            var time = Number(title);
            var seconds = (time- Math.floor(time/60)*60);
            innerHtml += '<tr><th>' + Math.floor(time/60)+':'+(seconds<10?"0":"")+seconds + '</th></tr>';
        });
        innerHtml += '</thead><tbody>';

        bodyLines.forEach(function(body, i) {
            innerHtml += '<tr><td>' + body + '</td></tr>';
        });
        innerHtml += '</tbody>';

        var tableRoot = tooltipEl.querySelector('table');
        tableRoot.innerHTML = innerHtml;
    }

    // `this` will be the overall tooltip
    var position = this._chart.canvas.getBoundingClientRect();

    // Display, position, and set styles for font
    tooltipEl.style.opacity = 0.8;
    tooltipEl.style.backgroundColor ='#333333';
    tooltipEl.style.position = 'absolute';
    tooltipEl.style.left = position.left + window.pageXOffset + tooltipModel.caretX + 'px';
    tooltipEl.style.top = position.top + window.pageYOffset + 30 + 'px';
    tooltipEl.style.fontFamily = tooltipModel._bodyFontFamily;
    tooltipEl.style.fontSize = tooltipModel.bodyFontSize + 'px';
    tooltipEl.style.fontStyle = tooltipModel._bodyFontStyle;
    tooltipEl.style.padding = tooltipModel.yPadding + 'px ' + tooltipModel.xPadding + 'px';
    tooltipEl.style.pointerEvents = 'none';

    hoverLine.style.opacity = 1;
    hoverLine.style.borderLeft = "1px solid white";
    hoverLine.style.position = 'absolute';
    hoverLine.style.left = position.left + window.pageXOffset + tooltipModel.caretX + 'px';
    hoverLine.style.top = position.top + window.pageYOffset + "px";
    hoverLine.style.width=0;
    hoverLine.style.height = position.height + "px";
    hoverLine.style.pointerEvents = "none";

}

function generateHrZonesDatasets(zone, hrZone, length){
    var icolors = ["#cccccc","#d4ff71","#e7e300","#e7b100","#e43636"];
    var fcLimit = new Array(length);
    if (zone == 5){
        hrZone +=5;
    }
    fcLimit.fill(hrZone);
    var fill = zone==1?true: "-1";

    return {
        type: 'line',
        label: "Intensité "+zone,
        data: fcLimit,
        fill: fill,
        backgroundColor: icolors[zone-1]+"bb",
        borderColor: icolors[zone-1],
        borderWidth:1,
        pointHoverRadius: 0,
    }
}

function getDayGraphConfig(hrLabels,hrData, hrZones){
    var len = hrData.length;
    return {
        type: 'line',
        data: {
            labels:hrLabels,
            datasets: [
                {
                    label: "Fréquence cardiaque",
                    data: hrData,
                    fill: false,
                    backgroundColor: 'blue',
                    borderColor: 'blue',
                    borderWidth:1,
                    pointHoverRadius: 0,
                },
                generateHrZonesDatasets(1, hrZones[0], len),
                generateHrZonesDatasets(2, hrZones[1], len),
                generateHrZonesDatasets(3, hrZones[2], len),
                generateHrZonesDatasets(4, hrZones[3], len),
                generateHrZonesDatasets(5, hrZones[4], len),
            ]
        },
        options: {
            responsive: true,
            animation: {
                duration: 0 // general animation time
            },
            hover: {
                animationDuration: 0 // duration of animations when hovering an item
            },
            responsiveAnimationDuration: 0, // animation duration after a resize
            maintainAspectRatio: true,
            title: {
                display: false
            },
            legend:{
                display: false,
                onClick:null
            },
            tooltips: {
                enabled:true,
                mode:'index',
                position:'oncurve',
                intersect: false,
                callbacks: {
                    title: function(tooltipItem, data) {
                        var title = data.labels[tooltipItem[0].index];
                        var time = Number(title);
                        var seconds = (time- Math.floor(time/60)*60);
                        return 'Temps: '+(Math.floor(time/60)+'min'+(seconds<10?"0":"")+seconds);
                    },
                    label: function(tooltipItem, data) {
                        var label = data.datasets[tooltipItem.datasetIndex].label || '';

                        if (label){
                            label +=": ";
                        }
                        if (tooltipItem.datasetIndex == 0){
                            label += tooltipItem.yLabel+'bpm';
                            return label;
                        }

                    }
                },
            },
            hover: {
                mode: 'nearest',
                intersect: false,
                axis:'x',
                animationDuration: 200,
            },
            scales: {
                xAxes: [{
                    display: false,
                }],
                yAxes: [{
                    display: true,
                    ticks :{
                        min: 60,
                        max: hrZones[4],
                    },
                    scaleLabel: {
                        display: false,
                        labelString: 'Fréquence cardiaque'
                    },
                    gridLines:{
                        color:'rgb(100,100,100)'
                    }
                }]
            },
            elements:{
                point :{
                    pointStyle:'circle',
                    radius:0
                },
                line: {
                    tension: 0 // disables bezier curves
                }
            }
        }
    };
}
