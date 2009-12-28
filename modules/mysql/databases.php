<?php

require_once('session/start.php');
require_once('inc/icons.php');
require_role(array(ROLE_SYSTEMUSER));

global $prefix;

require_once('mysql.php');

$output_something = true;


if (isset($_GET['action']))
  switch ($_GET['action'])
  {
    case 'delete_db':
      if (! has_mysql_database($_GET['db']))
        system_failure('Ungültige Datenbank');
      $sure = user_is_sure();
      if ($sure === NULL)
      {
        are_you_sure("action=delete_db&db={$_GET['db']}", "Möchten Sie die Datenbank »{$_GET['db']}« wirklich löschen?");
        $output_something = false;
      }
      elseif ($sure === true)
      {
        delete_mysql_database($_GET['db']);
        header("Location: ?");
        $output_something = false;
      }
      elseif ($sure === false)
      {
        header("Location: ?");
        $output_something = false;
      }
      break;
    case 'delete_user':
      if (! has_mysql_user($_GET['user']))
        system_failure('Ungültiger Benutzer');
      $sure = user_is_sure();
      if ($sure === NULL)
      {
        are_you_sure("action=delete_user&user={$_GET['user']}", "Möchten Sie den Benutzer »{$_GET['user']}« wirklich löschen?");
        $output_something = false;
      }
      elseif ($sure === true)
      {
        delete_mysql_account($_GET['user']);
        header("Location: ?");
        $output_something = false;
      }
      elseif ($sure === false)
      {
        header("Location: ?");
        $output_something = false;
      }
      break;
    case 'change_pw':
      check_form_token('mysql_databases');
      set_mysql_password($_POST['mysql_username'], $_POST['mysql_password']);
      header("Location: ?");
      $output_something = false;
      break;
    default:
      system_failure("Diese Funktion scheint noch nicht eingebaut zu sein!");
  }


$dbs = get_mysql_databases($_SESSION['userinfo']['uid']);
$users = get_mysql_accounts($_SESSION['userinfo']['uid']);

if (isset($_POST['accesseditor']))
{
  check_form_token('mysql_databases');
  /* Eine neue Datenbank */
  if ($_POST['new_db'] != '')
  {
    create_mysql_database($_POST['new_db']);
    if (isset($_POST['access']['new']))
    {
      $_POST['access'][$_POST['new_db']] = array();
      foreach ($users as $user)
        $user = $user['username'];
        if (in_array($user, $_POST['access']['new']))
          array_push($_POST['access'][$_POST['new_db']], $user);
      if (($_POST['new_user'] != '') and (in_array('new', $_POST['access']['new'])))
        array_push($_POST['access'][$_POST['new_db']], $_POST['new_user']);
    }
  }

  /* Ein neuer Account soll angelegt werden */
  if ($_POST['new_user'] != '')
  {
    create_mysql_account($_POST['new_user']);
    foreach ($dbs as $db)
      $db = $db['name'];
      if (isset($_POST['access'][$db]) and (in_array('new', $_POST['access'][$db])))
        array_push($_POST['access'][$db], $_POST['new_user']);
  }
  
  if (($_POST['new_user'] != '') or ($_POST['new_db'] != ''))
  {
    $dbs = get_mysql_databases($_SESSION['userinfo']['uid']);
    $users = get_mysql_accounts($_SESSION['userinfo']['uid']);
  }

  foreach ($dbs as $db)
  {
    $db = $db['name'];
    foreach ($users as $user)
    {
      $user = $user['username'];
      if (! isset($_POST['access'][$db]))
        set_mysql_access($db, $user, false);
      else
        set_mysql_access($db, $user, in_array($user, $_POST['access'][$db]));
    }
  }
  $mysql_access = NULL;
}

if ($output_something)
{

  output('<h3>MySQL-Datenbanken</h3>
  <p>Hier können Sie Ihre MySQL-Datenbanken verwalten. Die Einstellungen werden mit einer leichten Verzögerung (maximal 1 Minute) in das System übertragen. Bitte beachten Sie, dass neue Zugänge also nicht umgehend funktionieren.</p>
  <p><strong>Hinweis:</strong> In dieser Matrix sehen Sie links die Datenbanken und oben die Benutzer, die Sie eingerichtet haben.
  In die leeren Eingabefelder können Sie den Namen eines neuen Benutzers bzw. einer neuen Datenbank eintragen. Sofern Sie noch keine Datenbank(en) oder Benutzer eingerichtet haben, erscheinen nur die Eingabefelder. Vergessen Sie nicht, nach der Erstellung eines neuen Benutzerkontos dem betreffenden Benutzer ein Passwort zu setzen (s. unten auf dieser Seite). Der Name von Datenbanken und Datenbank-Benutzern muss mit dem Namen des System-Benutzeraccounts übereinstimmen oder mit diesem und einem nachfolgenden Unterstrich beginnen. Z.B. kann der System-Benutzer <em>bernd</em> die MySQL-Accounts <em>bernd</em> und <em>bernd_2</em> erzeugen. Aufgrund einer Beschränkung des MySQL-Servers dürfen Benutzernamen allerdings zur Zeit nur 16 Zeichen lang sein.</p>');

  $form = '
  <table>
  <tr><th>&#160;</th><th style="background-color: #729bb3; color: #fff;padding: 0.2em;" colspan="'.(count($users)+1).'">Benutzerkonten</th></tr>
  <tr><th style="background-color: #729bb3; color: #fff;padding: 0.2em; text-align: left;">Datenbanken</th>';

  foreach ($users as $user)
  {
    $username = str_replace('_', '_ ', $user['username']);
    $desc = ($user['description'] ? $user['description'].' (Erstellt: '.$user['created'].')' : 'Erstellt: '.$user['created']);
    $form .= "<th><span title=\"{$desc}\">{$username}</span><br />".internal_link("", icon_delete("Benutzer »{$user['username']}« löschen"), "action=delete_user&user={$user['username']}")."</th>";
  }
  $form .= '<th><input type="text" name="new_user" size="10" value="" /><br />'.icon_add().'</th></tr>
';

  array_push($users, array('username' => "new", 'description' => NULL));

  foreach($dbs as $db)
  {
    $desc = ($db['description'] ? $db['description'].' (Erstellt: '.$db['created'].')' : 'Erstellt: '.$db['created']);
    $form .= "<tr><td style=\"border: 0px; font-weight: bold; text-align: right;\"><span title=\"{$desc}\">{$db['name']}</span>&#160;".internal_link("", icon_delete("Datenbank »{$db['name']}« löschen"), "action=delete_db&db={$db['name']}")."</td>";
    foreach ($users as $user)
      $form .= '<td style="text-align: center;"><input type="checkbox" id="'.$db['name'].'_'.$user['username'].'" name="access['.$db['name'].'][]" value="'.$user['username'].'" '.(get_mysql_access($db['name'], $user['username']) ? 'checked="checked" ' : '')." /></td>";
    $form .= "</tr>\n";
  }

  $form .= '
  <tr><td style="border: 0px; font-weight: bold; text-align: right;"><input type="text" name="new_db" size="15" value="" />'.icon_add().'</td>';
  foreach ($users as $user)
    $form .= '<td style="text-align: center;"><input type="checkbox" id="new_'.$user['username'].'" name="access[new][]" value="'.$user['username'].'" /></td>';
  $form .= '</tr>
  </table>
  <p><input type="submit" name="accesseditor" value="Speichern" /></p>';

  
  output(html_form('mysql_databases', 'databases', '', $form));

  $users = get_mysql_accounts($_SESSION['userinfo']['uid']);



  $my_users = array();
  foreach ($users as $u)
  {
    $my_users[$u['username']] = $u['username'];
  }
  $form = '<div>
  <label for="mysql_username">Benutzername:</label>&#160;'.html_select('mysql_username', $my_users).'
  &#160;&#160;&#160;
  <label for="password">Passwort:</label>&#160;<input type="password" name="mysql_password" id="password" />
  &#160;&#160;<input type="submit" value="Setzen" />
</div>';


  output('<h4>Passwort ändern</h4>
  <p>Hier können Sie das Passwort eines MySQL-Benutzeraccounts ändern bzw. neu setzen</p>

  '.html_form('mysql_databases', 'databases', 'action=change_pw', $form).'<br />');

}


?>
