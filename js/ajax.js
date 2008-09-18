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
