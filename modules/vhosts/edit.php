<?php

require_once('inc/debug.php');
require_once('inc/security.php');

require_once('vhosts.php');

$title = "Subdomain bearbeiten";
$section = 'vhosts_vhosts';

require_role(ROLE_SYSTEMUSER);

$id = (int) $_GET['vhost'];
$vhost = empty_vhost();

if ($id != 0)
  $vhost = get_vhost_details($id);

DEBUG($vhost);
if ($id == 0) {
  output("<h3>Neue Subdomain anlegen</h3>");
  $title = "Subdomain anlegen";
}
else {
  output("<h3>Subdomain bearbeiten</h3>");
}

output("<script type=\"text/javascript\">
  
  function selectedDomain() {
    var selected;
    selected=document.getElementById('domain').options.selectedIndex;
    return document.getElementById('domain').options.item(selected).text;
    }
  
  function defaultDocumentRoot() {
    var hostname;
    if (document.getElementById('hostname').value == '') 
      hostname = selectedDomain();
    else
      hostname = document.getElementById('hostname').value + '.' + selectedDomain();
    document.getElementById('defaultdocroot').firstChild.nodeValue = 'websites/' + hostname + '/htdocs';
    useDefaultDocroot();
  }
  
  function useDefaultDocroot() {
    var do_it = (document.getElementById('use_default_docroot').checked == true);
    var inputfield = document.getElementById('docroot');
    inputfield.disabled = do_it;
    if (do_it) {
      document.getElementById('docroot').value = document.getElementById('defaultdocroot').firstChild.nodeValue;
    }
  }
  
  function showAppropriateLines() {
    if (document.getElementById('vhost_type_regular').checked == true) {
      document.getElementById('block_webapp').style.display = 'none';
      document.getElementById('block_localpath').style.display = '';
      document.getElementById('block_php').style.display = '';
    }
    else if ((document.getElementById('vhost_type_dav').checked == true) || 
         (document.getElementById('vhost_type_svn').checked == true)) {
      document.getElementById('block_webapp').style.display = 'none';
      document.getElementById('block_localpath').style.display = 'none';
      document.getElementById('block_php').style.display = 'none';
    }
    else if (document.getElementById('vhost_type_webapp').checked == true) {
      document.getElementById('block_webapp').style.display = '';
      document.getElementById('block_localpath').style.display = 'none';
      document.getElementById('block_php').style.display = 'none';
    }
  }
  </script>");

$defaultdocroot = $vhost['domain'];
if (! $vhost['domain'])
  $defaultdocroot = $_SESSION['userinfo']['username'].'.schokokeks.org';
if ($vhost['hostname'])
  $defaultdocroot = $vhost['hostname'].'.'.$defaultdocroot;

$defaultdocroot = 'websites/'.$defaultdocroot.'/htdocs';

$is_default_docroot = ($vhost['docroot'] == NULL) || ($vhost['homedir'].'/'.$defaultdocroot == $vhost['docroot']);

$docroot = '';
if ($vhost['docroot'] == '')
  $docroot = $defaultdocroot;
else
  $docroot = substr($vhost['docroot'], strlen($vhost['homedir'])+1);

$s = (strstr($vhost['options'], 'aliaswww') ? ' checked="checked" ' : '');
$errorlog = (strstr($vhost['errorlog'], 'on') ? ' checked="checked" ' : '');
$form = "
  <table>
    <tr><th>Einstellung</th><th>aktueller Wert</th><th>System-Standard</th></tr>
    <tr><td>Name</td>
    <td><input type=\"text\" name=\"hostname\" id=\"hostname\" size=\"10\" value=\"{$vhost['hostname']}\" onchange=\"defaultDocumentRoot()\" /><strong>.</strong>".domainselect($vhost['domain_id'], 'onchange="defaultDocumentRoot()"');
$form .= "<br /><input type=\"checkbox\" name=\"options[]\" id=\"aliaswww\" value=\"aliaswww\" {$s}/> <label for=\"aliaswww\">Auch mit <strong>www</strong> davor.</label></td><td><em>keiner</em></td></tr>
    <tr><td>Verwendung</td>
        <td>
	  <input onclick=\"showAppropriateLines()\" type=\"radio\" name=\"vhost_type\" id=\"vhost_type_regular\" value=\"regular\" /><label for=\"vhost_type_regular\">&#160;Normal (selbst Dateien hinterlegen)</label><br />
	  <input onclick=\"showAppropriateLines()\" type=\"radio\" name=\"vhost_type\" id=\"vhost_type_dav\" value=\"dav\" /><label for=\"vhost_type_dav\">&#160;WebDAV</label><br />
	  <input onclick=\"showAppropriateLines()\" type=\"radio\" name=\"vhost_type\" id=\"vhost_type_svn\" value=\"svn\" /><label for=\"vhost_type_svn\">&#160;Subversion-Server</label><br />
	  <input onclick=\"showAppropriateLines()\" type=\"radio\" name=\"vhost_type\" id=\"vhost_type_webapp\" value=\"webapp\" /><label for=\"vhost_type_webapp\">&#160;Eine vorgefertigte Applikation nutzen</label>
	</td>
	<td>&#160;</td>
    </tr>
    <tr id=\"block_webapp\">
      <td>Vorgefertigte Applikation</td>
      <td><select name=\"webapp\" size=\"1\"><option value=\"drupal-5\">Drupal 5.x</option></select>
      </td>
	<td>&#160;</td>
    </tr>
    <tr id=\"block_localpath\"><td>Lokaler Pfad</td>
    <td><input type=\"checkbox\" id=\"use_default_docroot\" name=\"use_default_docroot\" value=\"1\" onclick=\"useDefaultDocroot()\" ".($is_default_docroot ? 'checked="checked" ' : '')."/>&#160;<label for=\"use_default_docroot\">Standardeinstellung benutzen</label><br />
    <strong>".$vhost['homedir']."/</strong>&#160;<input type=\"text\" id=\"docroot\" name=\"docroot\" size=\"30\" value=\"".$docroot."\" ".($is_default_docroot ? 'disabled="disabled" ' : '')."/>
    </td>
    <td id=\"defaultdocroot\">{$defaultdocroot}</td></tr>
    <tr id=\"block_php\"><td>PHP</td>
    <td><select name=\"php\" id=\"php\">
      <option value=\"none\" ".($vhost['php'] == NULL ? 'selected="selected"' : '')." >kein PHP</option>
      <option value=\"mod_php\" ".($vhost['php'] == 'mod_php' ? 'selected="selected"' : '')." >als Apache-Modul</option>
      <option value=\"fastcgi\" ".($vhost['php'] == 'fastcgi' ? 'selected="selected"' : '')." >FastCGI</option>
    </select>
    </td>
    <td id=\"defaultphp\">als Apache-Modul</td></tr>
    <tr><td>SSL-Verschlüsselung</td>
    <td><select name=\"ssl\" id=\"ssl\">
      <option value=\"none\" ".($vhost['ssl'] == NULL ? 'selected="selected"' : '')." >SSL optional anbieten</option>
      <option value=\"http\" ".($vhost['ssl'] == 'http' ? 'selected="selected"' : '')." >kein SSL</option>
      <option value=\"https\" ".($vhost['ssl'] == 'https' ? 'selected="selected"' : '')." >nur SSL</option>
      <option value=\"forward\" ".($vhost['ssl'] == 'forward' ? 'selected="selected"' : '')." >Immer auf SSL umleiten</option>
    </select>
    </td>
    <td id=\"defaultssl\">SSL optional anbieten</td></tr>
    <tr>
      <td>Logfiles <span class=\"warning\">*</span></td>
      <td><select name=\"logtype\" id=\"logtype\">
      <option value=\"none\" ".($vhost['logtype'] == NULL ? 'selected="selected"' : '')." >keine Logfiles</option>
      <option value=\"anonymous\" ".($vhost['logtype'] == 'anonymous' ? 'selected="selected"' : '')." >anonymisiert</option>
      <option value=\"default\" ".($vhost['logtype'] == 'default' ? 'selected="selected"' : '')." >vollständige Logfile</option>
    </select><br />
    <input type=\"checkbox\" id=\"errorlog\" name=\"errorlog\" value=\"1\" ".($vhost['errorlog'] == 1 ? ' checked="checked" ' : '')." />&#160;<label for=\"errorlog\">Fehlerprotokoll (error_log) einschalten</label>
    </td>
    <td id=\"defaultlogtype\">keine Logfiles</td></tr>
    ";

$form .= '</table>
  <p><input type="submit" value="Speichern" />&#160;&#160;&#160;&#160;'.internal_link('vhosts.php', 'Abbrechen').'</p>
  <p class="warning"><span class="warning">*</span>Es ist im Moment Gegenstand gerichtlicher Außeinandersetzungen, ob die Speicherung von Logfiles auf Webservern
  zulässig ist. Wir weisen alle Nutzer darauf hin, dass sie selbst dafür verantwortlich sind, bei geloggten Nutzerdaten die
  Seitenbesucher darauf hinzuweisen. Wir empfehlen, wenn möglich, Logfiles abzuschalten oder anonymes Logging einzusetzen.</p>
';
output(html_form('vhosts_edit_vhost', 'save.php', 'action=edit&vhost='.$vhost['id'], $form));


?>
