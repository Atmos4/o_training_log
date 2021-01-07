
var maxTrimp;
var maxRpe;
var maxFatigue;
var maxTime;

$(document).ready(function(){
    loadContent('by_month', 'year='+$("#yearTitle").html(), setup);

    
    $('#prev').on('click', function(){
        var year = parseInt($("#yearTitle").html());
        var timeframe = $("#choose-time").val();
        $("#yearTitle").html(year-1);
        year=year-1;
        loadContent(timeframe, 'year='+year,setup);

    });
    $('#next').on('click', function(){
        var timeframe = $("#choose-time").val();
        var year = parseInt($("#yearTitle").html());
        $("#yearTitle").html(1+year);
        year=1+year;
        loadContent(timeframe, 'year='+year, setup);

    });
});

function setup(mode){
    chooseData();
    
    maxTrimp = Math.ceil(parseFloat(document.getElementById('max_trimp').value));
    maxRpe = Math.ceil(parseFloat(document.getElementById('max_rpe').value));
    maxFatigue = Math.ceil(parseFloat(document.getElementById('max_fatigue').value));
    maxTime = Math.ceil(parseFloat(document.getElementById('max_time').value));

    


    if (mode==="by_month"){
        $(".monthcollapse-wrapper").hide();
        $(".bytype-monthgraph").on('click',function(){
            $(this).siblings('.monthcollapse-wrapper').slideToggle();
            var canvas = $(this).siblings('.monthcollapse-wrapper').children();
            if (canvas.data("state")=="empty"){
                getData('GET','stats/month_graph.php?date='+canvas.data("date"),setupGraph,canvas);
            }
        });
    }

    
}

function setupGraph(request,canvas){
    var id = canvas.attr('id');

    if (request.readyState == 4 && request.status == 200) {
        if (request.responseText.startsWith('cURL')){
            document.getElementById(id).innerHTML = request.responseText;
            return;
        }
        canvas.data("state","loaded");
        var json = JSON.parse(request.responseText);
        var rpe = json.rpe;
        var labels = json.labels;
        var trimp = json.trimp;
        var time = json.time;
        var fatigue = json.fatigue;
        
        Chart.defaults.global.defaultFontColor = "white";

        var config = {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    type:'line',
                    label: 'Fatigue moyenne',
                    backgroundColor: 'white',
                    borderColor: 'white',
                    data: fatigue,
                    borderWidth: 3,
                    yAxisID: 'y-axis-1',
                    fill: false,
                },
                {
                    label: 'RPE',
                    backgroundColor: 'rgb(91, 91, 250)',
                    data: rpe,
                    borderWidth: 2,
                    yAxisID: 'y-axis-2'
                },
                {
                    label: 'TRIMP',
                    backgroundColor: 'rgb(190, 82, 82)',
                    data: trimp,
                    borderWidth: 2,
                    yAxisID: 'y-axis-3'
                },
                {
                    label: 'Volume horaire',
                    backgroundColor: '#aaaaaa',
                    data: time,
                    borderWidth: 2,
                    yAxisID: 'y-axis-4'
                }]
            },
            options: {
                responsive: true,
                title: {
                    display: false,
                },
                tooltips: {
                    mode: 'index',
                    intersect: false,
                    callbacks: {
                        label: function(tooltipItem, data) {
                            var label = data.datasets[tooltipItem.datasetIndex].label || '';
    
                            if (label){
                                label +=": ";
                            }
                            switch (tooltipItem.datasetIndex){
                                case 3:
                                    var time = tooltipItem.yLabel;
                                    var hours = Math.floor(time/60);
                                    var min = time - hours*60;
                                    label += hours+'h'+(min<10?'0':'')+min;
                                    break;
                                default:
                                    label += tooltipItem.yLabel;
                                    break;
                            }
                            return label;
    
                        }
                    },
                },
                scales: {
                    xAxes: [{
                        display: true,
                    }],
                    yAxes: [{
                        id : 'y-axis-1',
                        display: false,
                        ticks :{
                            max:maxFatigue,
                            min:0,
                        }
                    },
                    {
                        id : 'y-axis-2',
                        display: false,
                        ticks :{
                            max:maxRpe,
                            min:0,
                        }
                    },
                    {
                        id : 'y-axis-3',
                        display: false,
                        ticks :{
                            max:maxTrimp,
                            min:0,
                        }
                    },
                    {
                        id : 'y-axis-4',
                        display: false,
                        ticks :{
                            max:maxTime,
                            min:0,
                        }
                    }]
                },
            }
        };

        var ctx = document.getElementById(id).getContext('2d');
        var chart = new Chart(ctx, config);
	
    }
    
}

function loadContent(sendUrl,sendData, callback=null){
    $.ajax({
        type:'POST',
        url: 'stats/'+sendUrl+'.php',
        data: ''+sendData,
        beforeSend:function(){
            $("#loader").show();
        },
        success:function(html){
            $("#loader").hide();
            $('#content').html(html);
        },
        complete:function(){
            if (callback){
                callback(sendUrl);
            }
        }
    });   
}

function getData(method,url,callback,args=null){
    xmlhttp = new XMLHttpRequest();
    xmlhttp.onreadystatechange = function(){
        if (args)
            callback(this,args);
        else callback(this);
    }
    xmlhttp.open(method,url,true);
    xmlhttp.send();
}

function chooseTime(){
    var year = parseInt($("#yearTitle").html());
    var timeframe = $("#choose-time").val();
    loadContent(timeframe, 'year='+year,setup);
}

function chooseData(){
    switch($("#choose-data").val()){
        case "intens": 
            $('.intensity-graph').show();
            $('.type-graph').hide();
            break;
        case "types":
            $('.type-graph').show();
            $('.intensity-graph').hide();
            break;
    }
}