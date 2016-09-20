 
  function selectedDomain() {
      dom = $('#domain option:selected').text();
      return dom.match(/\S+/g)[0]
    }
  

  function getDefaultDocroot() {
    var hostname;
    if ($('#hostname').val() == '') 
      hostname = selectedDomain();
    else
      hostname = $('#hostname').val() + '.' + selectedDomain();
    return hostname + '/htdocs';
  }
  
  function useDefaultDocroot( default_docroot ) {
    var do_it = $('#use_default_docroot').prop('checked');
    var inputfield = $('#docroot');
    inputfield.attr('disabled', do_it);
    if (do_it) {
      $('#docroot').val(getDefaultDocroot());
    }
  }
  
  function showAppropriateLines() {
    type = $('input[name="vhost_type"]:checked').val();
    switch (type) {
      case "regular":
        $('#options_docroot').show();
        $('#options_scriptlang').show();
        $('#options_webapp').hide();
        break;
      case "dav":
        $('#options_docroot').show();
        $('#options_scriptlang').hide();
        $('#options_webapp').hide();
        break;
      case "svn":
        $('#options_docroot').hide();
        $('#options_scriptlang').hide();
        $('#options_webapp').hide();
        break;
      case "webapp":
        $('#options_docroot').hide();
        $('#options_scriptlang').hide();
        $('#options_webapp').show();
        break;
    }
  }


  function showhsts( event ) {
    var ssl = $('#ssl option:selected').val();
    if (ssl == 'forward') {
      $('#hsts_block').show();
      var cert = $('#cert option:selected').val();
      if (cert == '0') {
        $('#cert').val('-1');
      }
    } else
      $('#hsts_block').hide();
  }

  function hsts_preset( event ) {
    var seconds = $('#hsts_preset option:selected').val();
    if (seconds == 'custom') {
      $('#hsts_seconds').show();
      if ($('#hsts').val() < 0) {
        $('#hsts').val(2592000); /* 30 Tage */
      }
    } else {
      $('#hsts_seconds').hide();
      $('#hsts').val(seconds);
    }
  }


$(function() {

  $('#hostname').change(useDefaultDocroot);
  $('#domain').change(useDefaultDocroot);
  $('#use_default_docroot').change(useDefaultDocroot);
  useDefaultDocroot();

  $('.usageoption').change(showAppropriateLines);


  $('#ssl').change(showhsts);
  showhsts();
  if ($('#hsts_preset option:selected').val() != 'custom') {
    $('#hsts_seconds').hide();
  }
  $('#hsts_preset').change(hsts_preset);
  
});

