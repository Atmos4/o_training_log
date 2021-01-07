$(document).ready(function(){
    if ($('#view').val()==="planning"){
        loadContent('planning', 'date='+$("#yearTitle").html()+"-"+$('li.bold').attr("id")+"-01");
        $("#cal").removeClass("toggled");
        $("#plan").addClass("toggled");
    }
    else loadContent('month_view', 'date='+$("#yearTitle").html()+"-"+$('li.bold').attr("id")+"-01", weekSetup);

    $('ul#month_list > li').on('click', function(){
        
        var year = parseInt($("#yearTitle").html());
        var month = $('li.bold').attr("id");

        $('li.bold').removeClass('bold');
        $(this).addClass('bold');
        month = $(this).attr("id");

        if ($(".toggled").attr("id")=="plan") loadContent('planning', 'date='+year+'-'+month+'-01');
        else loadContent('month_view', 'date='+year+'-'+month+'-01',weekSetup);
    });
    
    $('#prev').on('click', function(){
        var year = parseInt($("#yearTitle").html());
        var month = $('li.bold').attr("id");
        $("#yearTitle").html(year-1);
        year=year-1;
        if ($(".toggled").attr("id")=="plan") loadContent('planning', 'date='+year+'-'+month+'-01');
        else loadContent('month_view', 'date='+year+'-'+month+'-01',weekSetup);

    });
    $('#next').on('click', function(){
        var year = parseInt($("#yearTitle").html());
        var month = $('li.bold').attr("id");
        $("#yearTitle").html(1+year);
        year=1+year;
        if ($(".toggled").attr("id")=="plan") loadContent('planning', 'date='+year+'-'+month+'-01');
        else loadContent('month_view', 'date='+year+'-'+month+'-01',weekSetup);
    });

    $(".toggle-planning").on('click', function(){
        $(".toggle-planning").removeClass("toggled");
        $(this).addClass("toggled");
        var year = parseInt($("#yearTitle").html());
        var month = $('li.bold').attr("id");

        if ($(this).attr("id")=="plan"){
            loadContent('planning', 'date='+year+'-'+month+'-01');
        }
        else{
            loadContent('month_view', 'date='+year+'-'+month+'-01',weekSetup);
        } 
    });


});

function weekSetup(){
    $('.week-panel-wrapper').hide();

    var token = document.getElementById('strava-token').value;
    if (token!==""){
        $('.sync-week').on('click',function(){
            var tstart = $(this).parent('.week-actions').siblings('#tstart').val();
            var tend = $(this).parent('.week-actions').siblings('#tend').val();
            $(this).addClass("rotate");
            stravaAuth(tstart, tend);
        });
    }

    $('.details-week').on('click', function(){
        if ($(this).hasClass('fa-chevron-down')){
            $(this).removeClass('fa-chevron-down');
            $(this).addClass('fa-chevron-up');
        }else if ($(this).hasClass('fa-chevron-up')){
            $(this).removeClass('fa-chevron-up');
            $(this).addClass('fa-chevron-down');
        }
        $(this).parents('tr').next('.week-details-panel').find('.week-panel-wrapper').slideToggle();
    });
}

function loadContent(sendUrl,sendData, callback=null){
    $.ajax({
        type:'POST',
        url: 'overview/'+sendUrl+'.php',
        data: ''+sendData,
        beforeSend:function(){
            $("#loader").show();
        },
        success:function(html){
            $("#loader").hide();
            $('#content').html(html);
        },
        complete:function(){
            setupModal('.plus-button-day');
            if (callback)
                callback();
        }
    });   
}

function saveWeekComment(elem){
    var date = elem.siblings('#week-date').val();
    var comment = elem.siblings('#week-comment').val();
    var coach_comm = elem.siblings('#coach-comment').val();
    
    var xmlhttp = new XMLHttpRequest();
    xmlhttp.onreadystatechange = function(){
        if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {
            toast(xmlhttp.responseText);
        }
    }
    xmlhttp.open("POST","overview/save_week_comment.php",true);
    xmlhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    xmlhttp.send("date="+date+"&comment="+comment+"&coach="+coach_comm);
}