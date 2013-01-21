<?php
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

require_once('inc/base.php');
require_once('inc/debug.php');

require_once('session/start.php');
require_once('su.php');

require_role(ROLE_SYSADMIN);


function su($type, $id) {
  $role = NULL;
  $admin_user = $_SESSION['userinfo']['username'];
  $_SESSION['admin_user'] = $admin_user;
  $role = find_role($id, '', True);
  if (!$role) {
    unset($_SESSION['admin_user']);
    return;
  }
  setup_session($role, $id);
  if ($type == 'c') {
    if (! (ROLE_CUSTOMER & $_SESSION['role'])) {
      session_destroy();
      system_failure('Es wurde ein "su" zu einem Kundenaccount angefordert, das war aber kein Kundenaccount!');
    }
  } elseif ($type == 'u') {
    if (! (ROLE_SYSTEMUSER & $_SESSION['role'])) {
      session_destroy();
      system_failure('Es wurde ein "su" zu einem Benutzeraccount angefordert, das war aber kein Benutzeraccount!');
    }
  } elseif ($type) {
    // wenn type leer ist, dann ist es auch egal
    system_failure('unknown type');
  }

  redirect('../../go/index/index');
  die();
}

if (isset($_GET['do']))
{
  if ($_SESSION['su_ajax_timestamp'] < time() - 30) {
    system_failure("Die su-Auswahl ist schon abgelaufen!");
  }
  $type = $_GET['do'][0];
  $id = (int) substr($_GET['do'], 1);
  su($type, $id);
}

if (isset($_POST['query']))
{
  check_form_token('su_su');
  $id = filter_input_general($_POST['query']);
  su(NULL, $id);
}

title("Benutzer wechseln");

output('<p>Hiermit können Sie (als Admin) das Webinterface mit den Rechten eines beliebigen anderen Benutzers benutzen.</p>
');

$debug = '';
if ($debugmode)
  $debug = 'debug&amp;';

html_header('
<link rel="stylesheet" href="http://code.jquery.com/ui/1.10.0/themes/base/jquery-ui.css">
<script type="text/javascript" src="http://code.jquery.com/jquery-1.9.0.js" ></script>
<script type="text/javascript" src="http://code.jquery.com/ui/1.10.0/jquery-ui.js" ></script>
');

output(html_form('su_su', '', '', '<label for="query"><strong>Suchtext:</strong></label> <input type="text" name="query" id="query" />
'));
output('
<script>
$("#query").autocomplete({
    source: "su_ajax",
    select: function( event, ui ) {
      if (ui.item) {
        window.location.href = "?do="+ui.item.id;
      }
}
 });
</script>');

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
