var old_email;
var pgpcheck_in_progress = false;

function populate_number(result) {
  var field = result.field;
  if (result.valid == 1) {
    document.querySelector("#"+field).value = result.number;
    document.querySelector("#"+field+"_feedback").innerHTML = '<img src="../../images/ok.png" style="height: 16px; width: 16px;" />';
  } else {
    document.querySelector("#"+field+"_feedback").innerHTML = '<img src="../../images/error.png" style="height: 16px; width: 16px;" alt="Nummer scheint nicht gültig zu sein" title="Nummer scheint nicht gültig zu sein" />';
  }
}

function check_number( field ) 
{
    return async function () {
        if (document.querySelector("#"+field).value.length > 0) {
            var number = document.querySelector("#"+field).value;
            var country = document.querySelector("#land").value;
            document.querySelector("#"+field).disabled = true;
            const response = await fetch("numbercheck?number="+encodeURIComponent(number)+"&country="+encodeURIComponent(country)+"&field="+field);
            const data = await response.json();
            document.querySelector("#"+field).disabled = false;
            populate_number(data);
        } else {
            document.querySelector("#"+field+"_feedback").innerHTML = '';
        }
    }
}

/*
function receive_pgpid(result) {
    if (result.status == 'found') {
        message = '<br>Es wurde ein PGP-Key auf einem Keyserver gefunden. Bitte prüfen Sie, ob die ID korrekt ist und Sie auch den dazu passenden privaten Schlüssel besitzen.';
        if (result.id == document.querySelector('#pgpid').value) {
            message = '';
        }
        document.querySelector('#pgpid').value = result.id;
        document.querySelector("#pgpid_feedback").innerHTML = '<img src="../../images/ok.png" style="height: 16px; width: 16px;" />'+message;
    } else if (result.status == 'unusable') {
        document.querySelector('#pgpid').value = result.id;
        document.querySelector('#pgpkey').closest('tr').style.display = "";
        document.querySelector("#pgpid_feedback").innerHTML = '<img src="../../images/error.png" style="height: 16px; width: 16px;" /><br>Es wurde ein Key gefunden, allerdings scheint dieser kaputt oder veraltet zu sein. Bitte geben Sie unten den kompletten aktuellen Key ein.';
    } else {
        document.querySelector('#pgpkey').closest('tr').style.display = "";
        document.querySelector("#pgpid_feedback").innerHTML = '<img src="../../images/error.png" style="height: 16px; width: 16px;" /><br>Es konnte kein PGP-Key zu dieser ID vom Keyserver-Netzwerk bezogen werden. Bitte geben Sie unten den kompletten Key ein.';
    }
}
*/

function email_change() {
    var new_email = document.querySelector('#email').value;
    if (document.querySelector('#designated-row') && new_email != old_email) {
        document.querySelector('#designated-row').style.display = "";
    } else {
        document.querySelector('#designated-row').style.display = "none";
    }
}

/*
async function searchpgp() {
    if (document.querySelector('#pgpid').value) {
        document.querySelector("#pgpid_feedback").innerHTML = '<img src="../../images/spinner.gif" style="height: 16px; width: 16px;" />';
        const response = await fetch("ajax_pgp?id="+encodeURIComponent(document.querySelector('#pgpid').value.replace(/\s/g, "")));
        const data = await response.json();
        receive_pgpid(data);
    } else if (document.querySelector('#email').value && ! document.querySelector('#pgpid').value) {
        document.querySelector("#pgpid_feedback").innerHTML = '<img src="../../images/spinner.gif" style="height: 16px; width: 16px;" />';
        const response = await fetch("ajax_pgp?q="+encodeURIComponent(document.querySelector('#email').value));
        const data = await response.json();
        receive_pgpid(data);
    }
}
*/
function usepgp_yes() {
    document.querySelector('#pgpid').closest('tr').style.display = "";
    document.querySelector('#pgpkey').closest('tr').style.display = "";
}

function usepgp_no() {
    document.querySelector('#pgpid').value = "";
    document.querySelector("#pgpid_feedback").innerHTML = '';
    document.querySelector('#pgpkey').value = '';
    document.querySelector('#pgpid').closest('tr').style.display = "none";
    document.querySelector('#pgpkey').closest('tr').style.display = "none";
}


ready(() => {
    document.querySelector('#telefon').addEventListener("focusout", (e) => check_number("telefon") );
    document.querySelector('#mobile').addEventListener("focusout", (e) => check_number("mobile") );
    document.querySelector('#telefax').addEventListener("focusout", (e) => check_number("telefax") );
    
    if (document.querySelector('#designated-row')) {
        //console.log(document.querySelector('#designated-row').style.display);
        document.querySelector('#designated-row').style.display = "none";
        old_email = document.querySelector('#email').value;
    }
    document.querySelector('#email').addEventListener("focusout", email_change);
    document.querySelector("#usepgp-yes").addEventListener("click", usepgp_yes);
    document.querySelector("#usepgp-no").addEventListener("click", usepgp_no);
    if (document.querySelector('#usepgp-no').checked) {
        document.querySelector('#pgpid').closest('tr').style.display = "none";
        document.querySelector('#pgpkey').closest('tr').style.display = "none";
    }
    // PGP-Suche deaktiviert weil sowieso disfunktional
    document.querySelector('#searchpgp').remove();
    //document.querySelector('#searchpgp').addEventListener("click", searchpgp);
});
