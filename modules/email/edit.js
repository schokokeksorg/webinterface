
  function moreForward(e)
  {
    e.preventDefault();
    last = $('div.vmail-forward:last');
    last_id = parseInt(last.attr('id').match(/\d+/g));
    new_id = ++last_id;
 
    if (new_id > 50) {
      alert("Jetzt wird's merkwürdig. Bitte nutzen Sie eine Mailingliste wenn Sie so viele Empfänger brauchen!");
      return;
    }


    var $clone = last.clone();
    $clone.attr('id',$clone.attr('id').replace(/\d+$/, function(str) { return parseInt(str) + 1; }) ); 

    // Find all elements in $clone that have an ID, and iterate using each()
    $clone.find('[id]').each(function() { 
      //Perform the same replace as above
      var $th = $(this);
      var newID = $th.attr('id').replace(/\d+$/, function(str) { return parseInt(str) + 1; });
      $th.attr('id', newID);
    });
    // Find all elements in $clone that have a name, and iterate using each()
    $clone.find('[name]').each(function() { 
      //Perform the same replace as above
      var $th = $(this);
      var newName = $th.attr('name').replace(/\d+$/, function(str) { return parseInt(str) + 1; });
      $th.attr('name', newName);
    });

    $clone.find('input:first-of-type').val('');
    $clone.find('select:first-of-type')
          .find('option:first-of-type').prop('selected', true);
    $clone.find('.warning').text('');
    $clone.find('.warning').hide();
    
    $clone.find('div.delete_forward').click(removeForward);
    $clone.find('input').on("change keyup paste", checkForward);
    
    last.after($clone);
  }

  function removeForward() 
  {
    div = $(this).closest('div.vmail-forward');
    input = div.find('input:first');
    input.val('');
    select = div.find('select:first');
    select.find('option:first').prop('selected', true);
    if ($('div.vmail-forward').length > 1) {
      div.remove();
    }
  }


  function removeUnneededForwards() {
    // Alle <div> nach dem Element mit der ID vmail_forward_1...
    $('div#vmail_forward_1 ~ div').each( function (el) {
      // ... die leere Eingabefelder haben ...
      if ($(this).find('input:first').val() == '') {
        // ... werden gelöscht
        $(this).remove();
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
  checkbox = $('#'+the_id)
  div = $('#'+the_id+'_config')
  if (checkbox.is(':checked')) {
    div.show(100);
  } else {
    div.hide(100);
  }

}


function hideUnchecked() {
  $('div.option_group').each( function(index) {
    the_id = this.id.replace('_config', '');
    checkbox = $('#'+the_id)
    div = $('#'+the_id+'_config')
    if (checkbox.is(':checked')) {
      div.show();
    } else {
      div.hide();
    }
  });
}


function checkForward( ) {
  input = $(this);
  val = input.val();
  atpos = val.indexOf('@');
  dot = val.lastIndexOf('.');
  if (atpos < 0 || val.length < atpos + 3 || dot < atpos || dot > val.length - 2) {
    return;
  }
  div = input.closest('div.vmail-forward');
}



$(document).ready(function(){
  // Automatisch Sternchen im Passwortfeld eintragen und entfernen
  $('#password').on('blur', refillPassword);
  $('#password').on('focus',clearPassword);    

  hideUnchecked();
  $('input.option_group').change(hideOrShowGroup);

  removeUnneededForwards();
  $('div.delete_forward').click(removeForward);
  $('#more_forwards').click(moreForward);

  $('div.vmail-forward input').on("change keyup paste", checkForward);
  
  // trigger setup of warnings
  $('div.vmail-forward input').change();


  document.querySelector("#ar_startdate").addEventListener("change", (e) => {
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
      });

});


