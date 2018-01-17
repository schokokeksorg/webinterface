function populate_bankinfo(result) {
  bank = result[0];
  if (bank.iban_ok == 1) {
    $("#iban_feedback").html('<img src="../../images/ok.png" style="height: 16px; width: 16px;" alt="" title="" />');
    if ($('#bankname').val() == "") 
      $('#bankname').val(bank.bankname);
    if ($('#bic').val() == "")  
      $('#bic').val(bank.bic);
  } else {
    $("#iban_feedback").html('<img src="../../images/error.png" style="height: 16px; width: 16px;" alt="IBAN scheint nicht gültig zu sein" title="IBAN scheint nicht gültig zu sein" />');
    $('#bankname').val("");
    $('#bic').val("");
  }
    
}

function searchbank() 
{
  var iban = $('#iban').val().replace(/\s/g, '');
  if (iban.substr(0,2) == "DE" && iban.length == 22) {
    $("#bankname").prop("disabled", true);
    $("#bic").prop("disabled", true);
    $.getJSON("sepamandat_banksearch?iban="+iban, populate_bankinfo)
      .always( function() {
        $("#bankname").prop("disabled", false);
        $("#bic").prop("disabled", false);
      });
  } else {
    $("#iban_feedback").html("");
  }
}

function copydata_worker( result ) {
  $("#kontoinhaber").val(result.kundenname);
  $("#adresse").val(result.adresse);
}

function copydata( event ) {
  event.preventDefault();
  var kunde = $.getJSON("sepamandat_copydata", copydata_worker);
}

function populate_iban(result) {
  info = result[0];
  $("#iban").val(info.iban);
  populate_bankinfo(result)
}

function ktoblz( event ) {
  event.preventDefault();
  var kto = $("#kto").val();
  var blz = $("#blz").val();
  $.getJSON("sepamandat_banksearch?kto="+kto+"&blz="+blz, populate_iban)
}

function showktoblz( event ) {
  event.preventDefault();
  $("#ktoblz_button").hide();
  $("#ktoblz_input").show();
}


$(function() {
    $('#iban').on("change keyup paste", searchbank );
    $("#copydata").click(copydata);
    $("#showktoblz").click(showktoblz);
    $("#ktoblz").click(ktoblz);
});
