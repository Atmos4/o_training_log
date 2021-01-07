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