/*
This file belongs to the Webinterface of schokokeks.org Hosting

Written 2008-2013 by schokokeks.org Hosting, namely
  Bernd Wurst <bernd@schokokeks.org>
  Hanno Böck <hanno@schokokeks.org>

To the extent possible under law, the author(s) have dedicated all copyright and related and neighboring rights to this software to the public domain worldwide. This software is distributed without any warranty.

You should have received a copy of the CC0 Public Domain Dedication along with this software. If not, see 
http://creativecommons.org/publicdomain/zero/1.0/

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
