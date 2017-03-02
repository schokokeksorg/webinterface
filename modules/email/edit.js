
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


  $.datepicker.regional['de'] = {clearText: 'löschen', clearStatus: 'aktuelles Datum löschen',
                closeText: 'schließen', closeStatus: 'ohne Änderungen schließen',
                prevText: '< zurück', prevStatus: 'letzten Monat zeigen',
                nextText: 'vor >', nextStatus: 'nächsten Monat zeigen',
                currentText: 'heute', currentStatus: '',
                monthNames: ['Januar','Februar','März','April','Mai','Juni',
                'Juli','August','September','Oktober','November','Dezember'],
                monthNamesShort: ['Jan','Feb','Mär','Apr','Mai','Jun',
                'Jul','Aug','Sep','Okt','Nov','Dez'],
                monthStatus: 'anderen Monat anzeigen', yearStatus: 'anderes Jahr anzeigen',
                weekHeader: 'Wo', weekStatus: 'Woche des Monats',
                dayNames: ['Sonntag','Montag','Dienstag','Mittwoch','Donnerstag','Freitag','Samstag'],
                dayNamesShort: ['So','Mo','Di','Mi','Do','Fr','Sa'],
                dayNamesMin: ['So','Mo','Di','Mi','Do','Fr','Sa'],
                dayStatus: 'Setze DD als ersten Wochentag', dateStatus: 'Wähle D, M d',
                dateFormat: 'dd.mm.yy', firstDay: 1, 
                initStatus: 'Wähle ein Datum', isRTL: false};
  $.datepicker.setDefaults( $.datepicker.regional[ "de" ] );
  $.datepicker.setDefaults({
    dateFormat: 'yy-mm-dd',
    minDate: 1,
    maxDate: "+2m"

    });

  $('#ar_startdate').datepicker();
  $('#ar_startdate').change(function () {
    $('#ar_valid_from_date').prop('checked', true)
    mindate = $('#ar_startdate').datepicker("getDate");
    mindate.setDate(mindate.getDate()+1);
    $('#ar_enddate').datepicker("option", "minDate", mindate);
    maxdate = $('#ar_startdate').datepicker("getDate");
    maxdate.setDate(maxdate.getDate()+60);
    $('#ar_enddate').datepicker("option", "maxDate", maxdate);
    });

  $('#ar_enddate').datepicker();
  $('#ar_enddate').datepicker("option", "minDate", $('#ar_startdate').val());
  $('#ar_enddate').change(function () {
    $('#ar_valid_until_date').prop('checked', true)
    });
});


