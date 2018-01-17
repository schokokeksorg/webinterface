function populate_number(result) {
  var field = result.field;
  if (result.valid == 1) {
    $("#"+field).val(result.number);
    $("#"+field+"_feedback").html('<img src="../../images/ok.png" style="height: 16px; width: 16px;" />');
  } else {
    $("#"+field+"_feedback").html('<img src="../../images/error.png" style="height: 16px; width: 16px;" alt="Nummer scheint nicht gültig zu sein" title="Nummer scheint nicht gültig zu sein" />');
  }
}

function check_number( field ) 
{
    return function () {
        if ($("#"+field).val().length > 0) {
            var number = $("#"+field).val();
            var country = $("#land").val();
            $("#"+field).prop("disabled", true);
            $.getJSON("numbercheck?number="+encodeURIComponent(number)+"&country="+encodeURIComponent(country)+"&field="+field, populate_number)
                .always( function() {
                    $("#"+field).prop("disabled", false);
                });
        } else {
            $("#"+field+"_feedback").html('');
        }
    }
}

$(function() {
    $('#telefon').on("change paste", check_number("telefon") );
    $('#mobile').on("change paste", check_number("mobile") );
    $('#telefax').on("change paste", check_number("telefax") );
});
