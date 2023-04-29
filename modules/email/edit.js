
  function moreForward(e)
  {
    e.preventDefault();
    last = [...document.querySelectorAll('div.vmail-forward')].at(-1);
    last_id = parseInt(last.id.match(/\d+/g));
    new_id = ++last_id;
 
    if (new_id > 50) {
      alert("Jetzt wird's merkwürdig. Bitte nutzen Sie eine Mailingliste wenn Sie so viele Empfänger brauchen!");
      return;
    }


    var clone = last.cloneNode(true);
    clone.id = clone.id.replace(/\d+$/, function(str) { return parseInt(str) + 1; }); 

    // Find all elements in $clone that have an ID, and iterate using each()
    clone.querySelectorAll('[id]').forEach(el => { 
      //Perform the same replace as above
      el.id = el.id.replace(/\d+$/, function(str) { return parseInt(str) + 1; });
    });
    // Find all elements in $clone that have a name, and iterate using each()
    clone.querySelectorAll('[name]').forEach(el => { 
      //Perform the same replace as above
      el.name = el.name.replace(/\d+$/, function(str) { return parseInt(str) + 1; });
    });

    clone.querySelector('input').value = '';
    
    clone.querySelector('div.delete_forward').addEventListener("click", removeForward);
    clone.querySelector('input').addEventListener("change", checkForward);
    clone.querySelector('input').addEventListener("keyup", checkForward);
    clone.querySelector('input').addEventListener("paste", checkForward);
    
    last.after(clone);
  }

  function removeForward(ev) 
  {
    div = this.closest('div.vmail-forward');
    input = div.querySelector('input');
    input.value = '';
    if ([...document.querySelectorAll('div.vmail-forward')].length > 1) {
      div.remove();
    }
  }


  function removeUnneededForwards() {
    // Alle <div> nach dem Element mit der ID vmail_forward_1...
    document.querySelectorAll('div#vmail_forward_1 ~ div').forEach(el => {
      // ... die leere Eingabefelder haben ...
      if (el.querySelector('input').value == '') {
        // ... werden gelöscht
        el.remove();
      }
      });
  }

  function clearPassword() {
    var input = document.getElementById('password');
    if (input.value == '**********') {
      input.value = '';
    }
    input.style.color = '#000';
    /* FIXME: Keine Ahnung, warum das notwendig ist. Mit dem tut es was es soll.  */
    input.focus();
  }

  function refillPassword() {
    var input = document.getElementById('password');
    if (input.value == '') {
      input.value = input.defaultValue;
    }
    if (input.value == '**********') {
      input.style.color = '#aaa';
    }
  }


function hideOrShowGroup( ev ) {
  checkbox = ev.target;
  the_id = checkbox.id;
  checkbox = document.querySelector('#'+the_id)
  div = document.querySelector('#'+the_id+'_config')
  if (checkbox.checked) {
    div.style.display = "";
  } else {
    div.style.display = "none";
  }

}


function hideUnchecked() {
  document.querySelectorAll('div.option_group').forEach(index => {
    the_id = index.id.replace('_config', '');
    checkbox = document.querySelector('#'+the_id)
    div = document.querySelector('#'+the_id+'_config')
    if (checkbox.checked) {
      div.style.display = "";
    } else {
      div.style.display = "none";
    }
  });
}


function checkForward(ev) {
  input = ev.target;
  val = input.value;
  atpos = val.indexOf('@');
  dot = val.lastIndexOf('.');
  if (atpos < 0 || val.length < atpos + 3 || dot < atpos || dot > val.length - 2) {
    return;
  }
  div = input.closest('div.vmail-forward');
  // FIXME: Diese Funktion prüft nur und macht nichts
}


function ar_startdate_changed(e) 
{
      document.querySelector("#ar_enddate").min = document.querySelector("#ar_startdate").value;
      startdate = new Date(document.querySelector("#ar_startdate").value)
      minenddate = new Date(startdate);
      minenddate.setDate(startdate.getDate() + 1);
      document.querySelector("#ar_enddate").min = minenddate.toISOString().split("T")[0];
      if (document.querySelector("#ar_enddate").value < document.querySelector("#ar_startdate").value) {
          document.querySelector("#ar_enddate").value = minenddate.toISOString().split("T")[0];
      }
      maxenddate = new Date(startdate);
      maxenddate.setDate(startdate.getDate() + 60);
      document.querySelector("#ar_enddate").max = maxenddate.toISOString().split("T")[0];

      document.querySelector("#ar_valid_from_date").checked = true;
 }



ready(() => {
  // Automatisch Sternchen im Passwortfeld eintragen und entfernen
  document.querySelector('#password').addEventListener('blur', refillPassword);
  document.querySelector('#password').addEventListener('focus',clearPassword);    

  hideUnchecked();
  document.querySelectorAll('input.option_group').forEach(el => el.addEventListener("change", hideOrShowGroup));

  removeUnneededForwards();
  document.querySelectorAll('div.delete_forward').forEach(el => el.addEventListener("click", removeForward));
  document.querySelector('#more_forwards').addEventListener("click", moreForward);

  document.querySelectorAll('div.vmail-forward input').forEach(el => el.addEventListener("change", checkForward));
  document.querySelectorAll('div.vmail-forward input').forEach(el => el.addEventListener("keyup", checkForward));
  document.querySelectorAll('div.vmail-forward input').forEach(el => el.addEventListener("paste", checkForward));
  
  document.querySelector("#ar_startdate").addEventListener("change", ar_startdate_changed)

});


