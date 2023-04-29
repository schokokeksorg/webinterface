function populate_bankinfo(result) {
  bank = result;
  if (bank.iban_ok == 1) {
    document.querySelector("#iban_feedback").innerHTML = '<img src="../../images/ok.png" style="height: 16px; width: 16px;" alt="" title="" />';
    if (document.querySelector('#bankname').value == "") 
      document.querySelector('#bankname').value = bank.bankname;
    if (document.querySelector('#bic').value == "")  
      document.querySelector('#bic').value = bank.bic;
  } else {
    document.querySelector("#iban_feedback").innerHTML = '<img src="../../images/error.png" style="height: 16px; width: 16px;" alt="IBAN scheint nicht gültig zu sein" title="IBAN scheint nicht gültig zu sein" />';
    document.querySelector('#bankname').value = "";
    document.querySelector('#bic').value = "";
  }
    
}

async function searchbank() 
{
  var iban = document.querySelector('#iban').value.toUpperCase().replace(/\s/g, '');
  if (iban.substr(0,2) == "DE" && iban.length == 22) {
    document.querySelector("#iban").value = iban;
    document.querySelector("#bankname").disabled = true;
    document.querySelector("#bic").disabled = true;
    const response = await fetch("sepamandat_banksearch?iban="+iban);
    const data = await response.json();
    populate_bankinfo(data);
    document.querySelector("#bankname").disabled = false;
    document.querySelector("#bic").disabled = false;
  } else {
    document.querySelector("#iban_feedback").innerHTML = "";
  }
}

function copydata_worker( result ) {
  document.querySelector("#kontoinhaber").value = result.kundenname;
  document.querySelector("#adresse").value = result.adresse;
}

async function copydata( event ) {
  event.preventDefault();
  const response = await fetch("sepamandat_copydata");
  const data = await response.json();
  copydata_worker(data);
}

function populate_iban(result) {
  document.querySelector("#iban").value = result.iban;
  populate_bankinfo(result)
}

async function ktoblz( event ) {
  event.preventDefault();
  var kto = document.querySelector("#kto").value;
  var blz = document.querySelector("#blz").value;
  const response = await fetch("sepamandat_banksearch?kto="+kto+"&blz="+blz);
  const data = await response.json();
  populate_iban(data);
}

function showktoblz( event ) {
  event.preventDefault();
  document.querySelector("#ktoblz_button").style.display = "none";
  document.querySelector("#ktoblz_input").style.display = "";
}

ready(() => { 
    document.querySelector('#iban').addEventListener("change", searchbank );
    document.querySelector('#iban').addEventListener("keyup", searchbank );
    document.querySelector('#iban').addEventListener("paste", searchbank );
    document.querySelector('#copydata').addEventListener("click", copydata);
    document.querySelector('#showktoblz').addEventListener("click", showktoblz);
    document.querySelector('#ktoblz').addEventListener("click", ktoblz);

    document.querySelector("#gueltig_ab_datum").addEventListener("change", (e) => {
        document.querySelector("#gueltig_ab_auswahl").checked = true;
        })
});

