



$(document).ready(function(){
    $('div#buttons').show();
    $('div#transfer').hide();
    $('div#external').hide();

    $('button#domain-external').click( function() {
        $('div#transfer').hide();
        $('div#buttons').hide();
        $('div#external').show();
    });
    $(".buttonset").buttonset();
 
});
