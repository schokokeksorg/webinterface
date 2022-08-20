/*
This file belongs to the Webinterface of schokokeks.org Hosting

Written by schokokeks.org Hosting, namely
  Bernd Wurst <bernd@schokokeks.org>
  Hanno Böck <hanno@schokokeks.org>

This code is published under a 0BSD license.

Nevertheless, in case you use a significant part of this code, we ask (but not require, see the license) that you keep the authors' names in place and return your changes to the public. We would be especially happy if you tell us what you're going to do with this code.
*/

var xmlHttp = null;

function ajax_request(from, query_string, target_function) {
  if (window.ActiveXObject) {
    try {
      xmlHttp= new ActiveXObject("Msxml2.XMLHTTP");
    } catch (e) {
      try {
        xmlHttp= new ActiveXObject("Microsoft.XMLHTTP");
      } catch (e) {
      }
    }
  } else if (window.XMLHttpRequest) {
    try {
      xmlHttp= new XMLHttpRequest();
    } catch (e) {
    }
  }

  if (xmlHttp) {
    xmlHttp.open('GET', from + '?' + query_string, true);
    xmlHttp.onreadystatechange = target_function;
    xmlHttp.send(null);
  }
}


function foo() {
  if (xmlHttp.readyState == 4) {
    text = 'Gefundene Begriffe: <br \/>' + xmlHttp.responseText;
    document.getElementById('output').innerHTML = text;
  }
} 
