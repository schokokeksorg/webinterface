var old_email;

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


function receive_pgpidcheck(result) {
    if (result.status == 'found') {
        $('#pgpid').val(result.id);
        $("#pgpid_feedback").html('<img src="../../images/ok.png" style="height: 16px; width: 16px;" />');
    } else {
        $('#pgpkey').closest('tr').show();
        $("#pgpid_feedback").html('<img src="../../images/error.png" style="height: 16px; width: 16px;" /><br>Es wurde kein PGP-Key zu dieser ID gefunden. Bitte geben Sie unten den kompletten Key ein.');

    }
}


function receive_pgpid(result) {
    if (result.status == 'found' && ! $('#pgpid').val()) {
        $('#pgpid').val(result.id);
        $("#pgpid_feedback").html('<img src="../../images/ok.png" style="height: 16px; width: 16px;" /><br>Es wurde ein PGP-Key auf einem Keyserver gefunden. Falls Sie einen anderen (oder gar keinen) PGP-Key nutzen möchten, ändern Sie dies bitte hier.');
    }
}

function pgpid_change() {
    val = $('#pgpid').val().replace(/\s/g, "");;
    if (val.length == 8 || val.length == 16 || val.length == 40) {
        $.getJSON("ajax_pgp?id="+encodeURIComponent(val), receive_pgpidcheck)
    }
}


function email_change() {
    var new_email = $('#email').val();
    if (new_email != old_email) {
        $('#designated-row').show();
    } else {
        $('#designated-row').hide();
    }
    if (new_email && ! $('#pgpid').val()) {
        $.getJSON("ajax_pgp?q="+encodeURIComponent(new_email), receive_pgpid)
    }
}

$(function() {
    $('#telefon').on("focusout", check_number("telefon") );
    $('#mobile').on("focusout", check_number("mobile") );
    $('#telefax').on("focusout", check_number("telefax") );
    
    if ($('#designated-row')) {
        $('#designated-row').hide();
        old_email = $('#email').val();
    }
    $('#email').on("focusout", email_change);
    $('#pgpid').on("focusout", pgpid_change);
    $('#pgpkey').closest('tr').hide();
});
