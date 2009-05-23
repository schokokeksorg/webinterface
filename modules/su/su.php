<?php

require_once('inc/base.php');
require_once('inc/debug.php');

require_once('session/start.php');
require_once('su.php');

require_role(ROLE_SYSADMIN);

if (isset($_GET['type']))
{
  check_form_token('su_su_ajax', $_GET['formtoken']);
  $role = NULL;
  $admin_user = $_SESSION['userinfo']['username'];
  if ($_GET['type'] == 'customer') {
    $role = find_role($_GET['id'], '', True);
    setup_session($role, $_GET['id']);
  } elseif ($_GET['type'] == 'systemuser') {
    $role = find_role($_GET['uid'], '', True);
    setup_session($role, $_GET['uid']);
  } else {
    system_failure('unknown type');
  }
  $_SESSION['admin_user'] = $admin_user;

  header('Location: ../../go/index/index');
  die();
}

if (isset($_POST['submit']))
{
  check_form_token('su_su');
  $id = (int) $_POST['destination'];
  $role = find_role($id, '', True);
  setup_session($role, $id);

  header('Location: ../../go/index/index');
  die();
}

$title = "Benutzer wechseln";

output('<h3>Benutzer wechseln</h3>
<p>Hiermit können Sie (als Admin) das Webinterface mit den Rechten eines beliebigen anderen Benutzers benutzen.</p>
');

$debug = '';
if ($debugmode)
  $debug = 'debug&amp;';

html_header('<script type="text/javascript" src="'.$prefix.'js/ajax.js" ></script>
<script type="text/javascript">

function doRequest() {
  ajax_request(\'su_ajax\', \''.$debug.'q=\'+document.getElementById(\'query\').value, got_response)
}

function keyPressed() {
  if(window.mytimeout) window.clearTimeout(window.mytimeout);
  window.mytimeout = window.setTimeout(doRequest, 500);
  return true;
}

function got_response() {
  if (xmlHttp.readyState == 4) {
    document.getElementById(\'response\').innerHTML = xmlHttp.responseText;
  }
}

</script>
');

output(html_form('su_su_ajax', '', '', '<strong>Suchtext:</strong> <input type="text" id="query" onkeyup="keyPressed()" />
'));
output('<div id="response"></div>
<div style="height: 3em;">&#160;</div>');

/*


$users = list_system_users();
$options = '';
foreach ($users as $user)
{
  $options .= "  <option value=\"{$user->uid}\">{$user->username} ({$user->uid})</option>\n";
}

output(html_form('su_su', 'su', '', '<p>Benutzer auswählen:
<select name="destination" size="1">
'.$options.'
</select>
<input type="submit" name="submit" value="zum Benutzer wechseln" />
</p>
'));

$customers = list_customers();
$options = '';
foreach ($customers as $customer)
{
  $options .= "  <option value=\"{$customer->id}\">{$customer->id} - ".htmlspecialchars($customer->name)."</option>\n";
}

output(html_form('su_su', 'su', '', '<p>Kunde auswählen:
<select name="destination" size="1">
'.$options.'
</select>
<input type="submit" name="submit" value="zum Kunden wechseln" />
</p>
'));

*/

?>
