var old_email;
var pgpcheck_in_progress = false;

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


function receive_pgpid(result) {
    if (result.status == 'found') {
        message = '<br>Es wurde ein PGP-Key auf einem Keyserver gefunden. Bitte prüfen Sie, ob die ID korrekt ist und Sie auch den dazu passenden privaten Schlüssel besitzen.';
        if (result.id == $('#pgpid').val()) {
            message = '';
        }
        $('#pgpid').val(result.id);
        $("#pgpid_feedback").html('<img src="../../images/ok.png" style="height: 16px; width: 16px;" />'+message);
    } else if (result.status == 'unusable') {
        $('#pgpid').val(result.id);
        $('#pgpkey').closest('tr').show();
        $("#pgpid_feedback").html('<img src="../../images/error.png" style="height: 16px; width: 16px;" /><br>Es wurde ein Key gefunden, allerdings scheint dieser kaputt oder veraltet zu sein. Bitte geben Sie unten den kompletten aktuellen Key ein.');
    } else {
        $('#pgpkey').closest('tr').show();
        $("#pgpid_feedback").html('<img src="../../images/error.png" style="height: 16px; width: 16px;" /><br>Es konnte kein PGP-Key zu dieser ID vom Keyserver-Netzwerk bezogen werden. Bitte geben Sie unten den kompletten Key ein.');
    }
}


function email_change() {
    var new_email = $('#email').val();
    if (new_email != old_email) {
        $('#designated-row').show();
    } else {
        $('#designated-row').hide();
    }
}

function searchpgp() {
    if ($('#pgpid').val()) {
        $("#pgpid_feedback").html('<img src="../../images/spinner.gif" style="height: 16px; width: 16px;" />');
        $.getJSON("ajax_pgp?id="+encodeURIComponent($('#pgpid').val().replace(/\s/g, "")), receive_pgpid)
    } else if ($('#email').val() && ! $('#pgpid').val()) {
        $("#pgpid_feedback").html('<img src="../../images/spinner.gif" style="height: 16px; width: 16px;" />');
        $.getJSON("ajax_pgp?q="+encodeURIComponent($('#email').val()), receive_pgpid)
    }
}

function usepgp_yes() {
    $('#pgpid').closest('tr').show();
    $('#pgpkey').closest('tr').show();
}

function usepgp_no() {
    $('#pgpid').val('');
    $("#pgpid_feedback").html('');
    $('#pgpkey').val('');
    $('#pgpid').closest('tr').hide();
    $('#pgpkey').closest('tr').hide();
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
    $(".buttonset").buttonset();
    $("#usepgp-yes").click(usepgp_yes);
    $("#usepgp-no").click(usepgp_no);
    if ($('#usepgp-no').is(':checked')) {
        $('#pgpid').closest('tr').hide();
        $('#pgpkey').closest('tr').hide();
    }
    $('#searchpgp').click(searchpgp);
});
