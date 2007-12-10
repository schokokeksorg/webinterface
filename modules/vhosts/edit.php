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
  var default_docroot;
 
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
    default_docroot = 'websites/' + hostname + '/htdocs';
    useDefaultDocroot();
  }
  
  function useDefaultDocroot() {
    var do_it = (document.getElementById('use_default_docroot').checked == true);
    var inputfield = document.getElementById('docroot');
    inputfield.disabled = do_it;
    if (do_it) {
      document.getElementById('docroot').value = default_docroot;
    }
  }
  
  function showAppropriateLines() {
    if (document.getElementById('vhost_type_regular').checked == true) {
      document.getElementById('options_docroot').style.display = 'block';
      document.getElementById('options_scriptlang').style.display = 'block';
      document.getElementById('options_webapp').style.display = 'none';
    }
    else if (document.getElementById('vhost_type_dav').checked == true) { 
      document.getElementById('options_docroot').style.display = 'block';
      document.getElementById('options_scriptlang').style.display = 'none';
      document.getElementById('options_webapp').style.display = 'none';
    }
    else if (document.getElementById('vhost_type_svn').checked == true) {
      document.getElementById('options_docroot').style.display = 'none';
      document.getElementById('options_scriptlang').style.display = 'none';
      document.getElementById('options_webapp').style.display = 'none';
    }
    else if (document.getElementById('vhost_type_webapp').checked == true) {
      document.getElementById('options_docroot').style.display = 'none';
      document.getElementById('options_scriptlang').style.display = 'none';
      document.getElementById('options_webapp').style.display = 'block';
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

$vhost_type = 'regular';
if ($vhost['is_dav'])
  $vhost_type = 'dav';
elseif ($vhost['is_svn'])
  $vhost_type = 'svn';
elseif ($vhost['is_webapp'])
  $vhost_type = 'webapp';

$applist = list_available_webapps();
$webapp_options = '';
foreach ($applist as $app)
  $webapp_options .= "<option value=\"{$app['id']}\">{$app['displayname']}</option>\n";


$form = "
<h4 style=\"margin-top: 2em;\">Name des VHost</h4>
    <div style=\"margin-left: 2em;\"><input type=\"text\" name=\"hostname\" id=\"hostname\" size=\"10\" value=\"{$vhost['hostname']}\" onchange=\"defaultDocumentRoot()\" /><strong>.</strong>".domainselect($vhost['domain_id'], 'onchange="defaultDocumentRoot()"');
$form .= "<br /><input type=\"checkbox\" name=\"options[]\" id=\"aliaswww\" value=\"aliaswww\" {$s}/> <label for=\"aliaswww\">Auch mit <strong>www</strong> davor.</label></div>

<div class=\"vhostsidebyside\">
<div class=\"vhostoptions\" id=\"options_docroot\" ".($vhost_type=='regular' || $vhost_type=='dav' ? '' : 'style="display: none;"').">
  <h4>Optionen</h4>
  <h5>Speicherort für Dateien (»Document Root«)</h5>
  <div style=\"margin-left: 2em;\">
    <input type=\"checkbox\" id=\"use_default_docroot\" name=\"use_default_docroot\" value=\"1\" onclick=\"useDefaultDocroot()\" ".($is_default_docroot ? 'checked="checked" ' : '')."/>&#160;<label for=\"use_default_docroot\">Standardeinstellung benutzen</label><br />
    <strong>".$vhost['homedir']."/</strong>&#160;<input type=\"text\" id=\"docroot\" name=\"docroot\" size=\"30\" value=\"".$docroot."\" ".($is_default_docroot ? 'disabled="disabled" ' : '')."/>
  </div>
</div>

<div class=\"vhostoptions\" id=\"options_scriptlang\" ".($vhost_type=='regular' ? '' : 'style="display: none;"').">
  <h5>Script-Sprache</h5>
  <div style=\"margin-left: 2em;\">
    <select name=\"php\" id=\"php\">
      <option value=\"none\" ".($vhost['php'] == NULL ? 'selected="selected"' : '')." >keine Scriptsprache</option>
      <option value=\"mod_php\" ".($vhost['php'] == 'mod_php' ? 'selected="selected"' : '')." >PHP als Apache-Modul</option>
      <option value=\"fastcgi\" ".($vhost['php'] == 'fastcgi' ? 'selected="selected"' : '')." >PHP als FastCGI</option>
      <!--  <option value=\"rubyonrails\" ".($vhost['php'] == 'rubyonrails' ? 'selected="selected"' : '')." >Ruby-on-Rails</option> -->
    </select>
  </div>
</div>

<div class=\"vhostoptions\" id=\"options_webapp\" ".($vhost_type=='webapp' ? '' : 'style="display: none;"').">
  <h4>Optionen</h4>
  <h5>Anwendung</h5>
  <select name=\"webapp\" id=\"webapp\" size=\"1\">
    {$webapp_options}
  </select>
  <p>Wenn Sie diese Option wählen, wird die Anwendung automatisch eingerichtet. Sie erhalten dann ihre Zugangsdaten per E-Mail.</p>
</div>

<h4>Verwendung</h4>
        <div style=\"margin-left: 2em;\">
	  <input class=\"usageoption\" onclick=\"showAppropriateLines()\" type=\"radio\" name=\"vhost_type\" id=\"vhost_type_regular\" value=\"regular\" ".(($vhost_type=='regular') ? 'checked="checked" ' : '')."/><label for=\"vhost_type_regular\">&#160;Normal (selbst Dateien hinterlegen)</label><br />
	  <input class=\"usageoption\" onclick=\"showAppropriateLines()\" type=\"radio\" name=\"vhost_type\" id=\"vhost_type_webapp\" value=\"webapp\" ".(($vhost_type=='webapp') ? 'checked="checked" ' : '')."/><label for=\"vhost_type_webapp\">&#160;Eine vorgefertigte Applikation nutzen</label><br />
	  <input class=\"usageoption\" onclick=\"showAppropriateLines()\" type=\"radio\" name=\"vhost_type\" id=\"vhost_type_dav\" value=\"dav\" ".(($vhost_type=='dav') ? 'checked="checked" ' : '')."/><label for=\"vhost_type_dav\">&#160;WebDAV</label><br />
	  <input class=\"usageoption\" onclick=\"showAppropriateLines()\" type=\"radio\" name=\"vhost_type\" id=\"vhost_type_svn\" value=\"svn\" ".(($vhost_type=='svn') ? 'checked="checked" ' : '')."/><label for=\"vhost_type_svn\">&#160;Subversion-Server</label>
	</div>
<br />
</div>

<h4 style=\"margin-top: 3em;\">Allgemeine Optionen</h4>
<div style=\"margin-left: 2em;\">
    <h5>SSL-Verschlüsselung</h5>
    <div style=\"margin-left: 2em;\">
    <select name=\"ssl\" id=\"ssl\">
      <option value=\"none\" ".($vhost['ssl'] == NULL ? 'selected="selected"' : '')." >SSL optional anbieten</option>
      <option value=\"http\" ".($vhost['ssl'] == 'http' ? 'selected="selected"' : '')." >kein SSL</option>
      <option value=\"https\" ".($vhost['ssl'] == 'https' ? 'selected="selected"' : '')." >nur SSL</option>
      <option value=\"forward\" ".($vhost['ssl'] == 'forward' ? 'selected="selected"' : '')." >Immer auf SSL umleiten</option>
    </select>
    </div>
    <h5>Logfiles <span class=\"warning\">*</span></h5>
    <div style=\"margin-left: 2em;\">
      <select name=\"logtype\" id=\"logtype\">
        <option value=\"none\" ".($vhost['logtype'] == NULL ? 'selected="selected"' : '')." >keine Logfiles</option>
        <option value=\"anonymous\" ".($vhost['logtype'] == 'anonymous' ? 'selected="selected"' : '')." >anonymisiert</option>
        <option value=\"default\" ".($vhost['logtype'] == 'default' ? 'selected="selected"' : '')." >vollständige Logfile</option>
      </select><br />
      <input type=\"checkbox\" id=\"errorlog\" name=\"errorlog\" value=\"1\" ".($vhost['errorlog'] == 1 ? ' checked="checked" ' : '')." />&#160;<label for=\"errorlog\">Fehlerprotokoll (error_log) einschalten</label>
    </div>
</div>
    ";

$form .= '
  <p><input type="submit" value="Speichern" />&#160;&#160;&#160;&#160;'.internal_link('vhosts.php', 'Abbrechen').'</p>
  <p class="warning"><span class="warning">*</span>Es ist im Moment Gegenstand gerichtlicher Außeinandersetzungen, ob die Speicherung von Logfiles auf Webservern
  zulässig ist. Wir weisen alle Nutzer darauf hin, dass sie selbst dafür verantwortlich sind, bei geloggten Nutzerdaten die
  Seitenbesucher darauf hinzuweisen. Wir empfehlen, wenn möglich, Logfiles abzuschalten oder anonymes Logging einzusetzen.</p>
';
output(html_form('vhosts_edit_vhost', 'save.php', 'action=edit&vhost='.$vhost['id'], $form));


?>
