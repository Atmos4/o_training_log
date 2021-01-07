var slider = document.getElementById("loadrange");
var output = document.getElementById("trainingload");

slider.oninput = function() {
    output.value = this.value;
};

$(".collapsible").on('click', function(){
    var content = $(this).next(".collcontent");
    var sign = $(this).children(".sign");
    if (content.height()==0){
        content.css("max-height",content.prop("scrollHeight")+"px");
        sign.html("-");
    } else {
        content.css("max-height","0");
        sign.html("+");
    } 
});