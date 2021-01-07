
$(document).ready(function(){

    $('.actions-wrapper #submit').hide();
    $('.actions-wrapper #reset').hide();

    setupModal('#addplanning');
    

    $('.change-day').on('click',function(){
        var newday = $(this).attr('id');
        location.replace("day_view.php?date="+newday);
    });

    
    var token = document.getElementById('strava-token').value;
    if (token!==""){
        var tstart = document.getElementById('start-day').value;
        var tend = document.getElementById('end-day').value;
        $('#sync-div').on('click',function(){
            $("#loader").show();
            stravaAuth(tstart, tend);
        });
    }
    if ($('#day_content').length){
        openDay(document.title);
    }
    if ($('.tab.planning.selected').length){
        openPlanning($('.tab.planning.selected'),$('.tab.planning.selected').attr('id'), $('.tab.planning.selected').css('backgroundColor'));
    }
    if ($('.tab.training.selected').length){
        openSeance($('.tab.training.selected'),$('.tab.training.selected').attr('id'), $('.tab.training.selected').css('backgroundColor'));
    }
     $('.tab.training').on('click',function(){
        openSeance($(this),$(this).attr('id'),$(this).css('backgroundColor'));
    });

    $('.tab.planning').on('click',function(){
        openPlanning($(this),$(this).attr('id'),$(this).css('backgroundColor'));
    });
    
    
    

});

//TODO
function openDay(str) {
    $("#loader").show();    
    loadContent("GET","day/day.php?date="+str,function() {
        if (this.readyState == 4 && this.status == 200) {
            document.getElementById("day_content").innerHTML = this.responseText;

            //Parce que textarea c'est du caca
            var comm = document.getElementById('commentaire');
            comm.style.height = "1px";
            comm.style.height = (25+comm.scrollHeight)+"px";
            $('#day-form .slideform').hide();
            
            var slider = document.getElementById("range-day");
            var output = document.getElementById("value-range-day");
            
            slider.oninput = function() {
                output.value = this.value;
            };

            if ($("#value-range-day").val()!=""){
                $("#daycollapse").css("max-height",$("#daycollapse").prop("scrollHeight")+"px");
                $("#daycollapse").prev(".collapsible").children(".sign").html("-");
            }

            setupCollapse();
            $("#loader").hide();
        }
    });
}

function openSeance(elem,str, color) {
    disableForm();
    $("#loader").show();
    loadContent("GET","day/seance.php?id="+str,function() {
        if (this.readyState == 4 && this.status == 200) {
            document.getElementById("seance_content").innerHTML = this.responseText;

            $('.selected').removeClass('selected');
            elem.addClass('selected');
            createGradient(color);

            fixTxtHeight();

            $('#day-form .slideform').hide();


            $('.slideform').hide();
            if ($("#value-range-seance").val()!=="") {
                $("#trcollapse").css("max-height",$("#trcollapse").prop("scrollHeight")+"px");
                $("#trcollapse").prev(".collapsible").children(".sign").html("-");
            }

            setupCollapse();

            
            var slider2 = document.getElementById("range-seance");
            var output2 = document.getElementById("value-range-seance");

            slider2.oninput = function() {
                output2.value = this.value;
            };

            var token = document.getElementById('strava_id').value;
            if (token!==""){
                loadHrGraph(token);
            }
            $("#loader").hide();
        }
    });
}

function loadHrGraph(id){
    loadContent("GET","day/hr_graph.php?id="+id, function() {
        if (this.readyState == 4 && this.status == 200) {
            if (this.responseText.startsWith('cURL')){
                document.getElementById('hr-graph').innerHTML = this.responseText;
                return;
            }
            var json = JSON.parse(this.responseText);
            var hrData = json.heartrate.data;
            var hrLabels = json.time.data;
            var hrz1 = Number(document.getElementById('hrz1').value);
            var hrz2 = Number(document.getElementById('hrz2').value);
            var hrz3 = Number(document.getElementById('hrz3').value);
            var hrz4 = Number(document.getElementById('hrz4').value);
            var fcMax = Number(document.getElementById('fcmax').value);
            var hrZones = [hrz1, hrz2, hrz3, hrz4,fcMax];
            
            Chart.defaults.global.defaultFontColor = "white";
            Chart.Tooltip.positioners.oncurve = function(elements, eventPosition) {
                return {
                    x: elements[0]._view.x,
                    y: elements[0]._view.y
                };
            }

            var config = getDayGraphConfig(hrLabels,hrData,hrZones);

            var ctx = document.getElementById('hr-canvas').getContext('2d');
            window.myLine = new Chart(ctx, config);
            $("#loader").hide();
        }
    });
}

function openPlanning(elem,str, color) {
    disableForm();
    $("#loader").show();
    loadContent("GET","day/planning_day.php?id="+str,function() {
        if (this.readyState == 4 && this.status == 200) {
            document.getElementById("seance_content").innerHTML = this.responseText;

            $('.selected').removeClass('selected');
            elem.addClass('selected');
            createGradient(color);

            fixTxtHeight();
            
            setupCollapse();
            $("#loader").hide();
        }
    });
}

function fixTxtHeight(){
    //Parce que textarea c'est du caca
    var txtareas = document.getElementsByClassName('std-txt');
    Array.from(txtareas).forEach(elem => {
        elem.style.height = "1px";
        elem.style.height = (25+elem.scrollHeight)+"px";
    });
}

function loadContent(method,url,callback){
    xmlhttp = new XMLHttpRequest();
    xmlhttp.onreadystatechange = callback;
    xmlhttp.open(method,url,true);
    xmlhttp.send();
}

function setupCollapse(){
    $(".collapsible").on('click', function(){
        var content = $(this).next(".collcontent");
        var sign = $(this).children(".sign");
        if (content.height()===0){
            content.css("max-height",content.prop("scrollHeight")+"px");
            sign.html("-");
        } else {
            content.css("max-height","0");
            sign.html("+");
        } 
    });
}

function createGradient(color){
    $('#gradient-wrapper').css("background","linear-gradient("+color+",rgba(255,0,0,0)");
}

function activateForm(){
    var form = ".form-editable";
    $(form + " .slideform").show();
    $(form + " input").prop('disabled',false);
    $(form + " textarea").prop('disabled',false);
    $(form + " select").prop('disabled',false);

    
    $(".actions-wrapper #submit").show();
    $(".actions-wrapper #reset").show();
    $(".actions-wrapper #change").hide();
}

function disableForm(){
    var form = ".form-editable";
    $(form + " .slideform").hide();
    $(form + " :input").prop('disabled',true);
    $(form + " textarea").prop('disabled',true);
    $(form + " select").prop('disabled',true);
    
    $('.actions-wrapper #submit').hide();
    $('.actions-wrapper #reset').hide();
    $(".actions-wrapper #change").show();
    $('.actions-wrapper #submit').prop('disabled',false);
    $('.actions-wrapper #reset').prop('disabled',false);
    $(".actions-wrapper #change").prop('disabled',false);
}

function submitForm(){
    $('#dayview-form').submit();
}

