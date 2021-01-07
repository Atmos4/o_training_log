function setupModal(trigger){

    $(trigger).on('click',function(){

        $('#planningform')[0].reset();
        $('#planningform #txt').html("");
        $('#planning-modal').show();
        $('#plan-date').html($(this).data("day"));
        $('#planningform #pdate').val($(this).data("day"));
    });

    var modal2 = document.getElementById("planning");
    window.onclick = function(event) {
        if (event.target == modal2) {
            $('#planning').hide();
            //$('.modal-content').css('backgroundColor','#444444');
            $('#planningform #pid').val("");
            $(".dropdown").hide();
            $(".multiSel").html("0");
            $("#common").html("");
        }
    }
}


$(".dropbutton").on("click", function(){
    $(".dropdown").toggle("fast");
});

$('.dropdown input[type="checkbox"]').on('click', function() {
    if ($(this).is(':checked')) {
        $('.multiSel').html(parseInt($('.multiSel').html())+1);
    } else {
        $('.multiSel').html(parseInt($('.multiSel').html())-1);
  
    }
});


/*function createGradient(color){
    $('#gradient-wrapper').css("background","-webkit-linear-gradient("+color+",rgba(0,0,0,0),rgba(0,0,0,0))");
    $('#gradient-wrapper').css("background","-moz-linear-gradient("+color+",rgba(255,0,0,0),rgba(255,0,0,0))");
    $('#gradient-wrapper').css("background","linear-gradient("+color+",rgba(255,0,0,0),rgba(255,0,0,0))");
}*/
