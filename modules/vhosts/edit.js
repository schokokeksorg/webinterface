 
  function selectedDomain() {
      dom = [...document.querySelectorAll('#domain option')].filter((el) => {return el.selected}).at(0).text
      return dom.match(/\S+/g)[0]
    }
  

  function getDefaultDocroot() {
    var hostname;
    if (document.querySelector('#hostname').value == '') 
      hostname = selectedDomain();
    else
      hostname = document.querySelector('#hostname').value + '.' + selectedDomain();
    return hostname + '/htdocs';
  }
  
  function useDefaultDocroot( default_docroot ) {
    var do_it = document.querySelector('#use_default_docroot').checked;
    var inputfield = document.querySelector('#docroot');
    inputfield.disabled = do_it;
    if (do_it) {
      document.querySelector('#docroot').value = getDefaultDocroot();
    }
  }
  
  function showAppropriateLines() {
    type = [...document.querySelectorAll('input[name="vhost_type"]')].filter((el) => {return el.checked}).at(0).value
    switch (type) {
      case "regular":
        document.querySelector('#options_docroot').style.display = '';
        document.querySelector('#options_scriptlang').style.display = '';
        break;
      case "dav":
        document.querySelector('#options_docroot').style.display = '';
        document.querySelector('#options_scriptlang').style.display = 'none';
        break;
      case "svn":
        document.querySelector('#options_docroot').style.display = 'none';
        document.querySelector('#options_scriptlang').style.display = 'none';
        break;
    }
  }


  function showhsts( event ) {
      ssl = [...document.querySelectorAll('#ssl option')].filter((el) => {return el.selected}).at(0).value
    if (ssl == 'forward') {
      document.querySelector('#hsts_block').style.display = '';
      cert = [...document.querySelectorAll('#cert option')].filter((el) => {return el.selected}).at(0).value
      if (cert == '') {
        document.querySelector('#cert').value = '-1';
      }
    } else {
      document.querySelector('#hsts_block').style.display = 'none';
    }
    show_hsts_opts();
  }

  function hsts_preset( event ) {
      seconds = [...document.querySelectorAll('#hsts_preset option')].filter((el) => {return el.selected}).at(0).value
    if (seconds == 'custom') {
      document.querySelector('#hsts_seconds').style.display = '';
      if (document.querySelector('#hsts').value < 0) {
        document.querySelector('#hsts').value = 2592000; /* 30 Tage */
      }
    } else {
      document.querySelector('#hsts_seconds').style.display = 'none';
      document.querySelector('#hsts').value = seconds;
    }
  }
  
  function show_hsts_opts( event ) {
    ssl = [...document.querySelectorAll('#ssl option')].filter((el) => {return el.selected}).at(0).value
    show_block = false;
    preload_enabled = false;
    if ( ssl == 'forward') {
        if (document.querySelector('#hsts').value > 0) {
            show_block = true;
            if (document.querySelector('#hsts_subdomains').checked) {
                preload_enabled = true;
            }
        }

    }
    if (document.querySelector('#hostname').value != '') {
        show_block = false;
    }
    if (show_block) {
        document.querySelector('#hsts_preload_options').style.display = '';
    } else {
        document.querySelector('#hsts_preload_options').style.display = 'none';
        document.querySelector('#hsts_subdomains').checked = false;
        document.querySelector('#hsts_preload').checked = false;
    }
    if (preload_enabled) {
        document.querySelector('#hsts_preload').disabled = false;
    } else {
        document.querySelector('#hsts_preload').disabled = true;
        document.querySelector('#hsts_preload').checked = false;
    }
  }
  

  function showAliasWWWOptions( event ) {
    if (document.querySelector('#aliaswww').checked) {
        document.querySelector('#aliaswww_option').style.display = '';
    } else {
        document.querySelector('#aliaswww_option').style.display = 'none';
    
    }
  }


ready(() => {

  document.querySelector('#hostname').addEventListener("change", useDefaultDocroot);
  document.querySelector('#domain').addEventListener("change", useDefaultDocroot);
  document.querySelector('#use_default_docroot').addEventListener("change", useDefaultDocroot);
  useDefaultDocroot();

  document.querySelector('.usageoption').addEventListener("change", showAppropriateLines);

  document.querySelector('#aliaswww').addEventListener("change", showAliasWWWOptions);
  showAliasWWWOptions();

  document.querySelector('#ssl').addEventListener("change", showhsts);
  document.querySelector('#hsts_select').style.display = '';
  showhsts();
  seconds = [...document.querySelectorAll('#hsts_preset option')].filter((el) => {return el.selected}).at(0).value
  if (seconds != 'custom') {
    document.querySelector('#hsts_seconds').style.display = 'none';
  }
  
  document.querySelector('#hsts_preset').addEventListener("change", hsts_preset);
  document.querySelector('#hsts_select').addEventListener("change", show_hsts_opts);
  document.querySelector('#hsts_subdomains').addEventListener("change", show_hsts_opts);
  show_hsts_opts();
  
});

