<?php

require_once('inc/debug.php');
require_once('inc/security.php');

require_once('vmail.php');

$section = 'email_vmail';
require_role(array(ROLE_SYSTEMUSER, ROLE_VMAIL_ACCOUNT));

$account = empty_account();
$id = (isset($_GET['id']) ? (int) $_GET['id'] : 0);

if ($id != 0)
  $account = get_account_details($id);

$accountlogin = false;
if ($_SESSION['role'] == ROLE_VMAIL_ACCOUNT) {
  $id = get_vmail_id_by_emailaddr($_SESSION['mailaccount']);
  $account = get_account_details($id, false);
  $accountlogin = true;
  $accountname = filter_input_general($_SESSION['mailaccount']);
}


DEBUG($account);
if ($id == 0) {
  title("E-Mail-Adresse anlegen");
}
else {
  if ($accountlogin) {
    title("Einstellungen für {$accountname}");
  } else {
    title("E-Mail-Adresse bearbeiten");
  }
}


$is_autoresponder = is_array($account['autoresponder']) && $account['autoresponder']['valid_from'] != NULL;
$is_forward = (count($account['forwards']) > 0);
$is_mailbox = ($account['password'] != NULL  ||  $id == 0);
$numforwards = max(count($account['forwards']), 1);

output("<script type=\"text/javascript\">
  
  var numForwards = {$numforwards};

  function moreForward()
  {
    numForwards += 1;

    P1 = document.createElement('p');

    TXT1 = document.createTextNode('Weiterleiten an ');

    INPUT = document.createElement('input');
    INPUT.type = 'text';
    INPUT.name = 'forward_to_' + numForwards;
    INPUT.value = '';

    P1.appendChild(TXT1);
    P1.appendChild(INPUT);

    P2 = document.createElement('p');

    TXT2 = document.createTextNode('Spam-Mails an diese Adresse ');

    SELECT = document.createElement('select');
    SELECT.name = 'spamfilter_action_' + numForwards;

    SELECT.options[0] = new Option('nicht filtern', 'none', 1);
    SELECT.options[1] = new Option('markieren und zustellen', 'tag', 0);
    SELECT.options[2] = new Option('nicht zustellen', 'delete', 0);

    P2.appendChild(TXT2);
    P2.appendChild(SELECT);

    DIV = document.createElement('div');
    DIV.className = 'vmail-forward';

    DIV.appendChild(P1);
    DIV.appendChild(P2);

    parent = document.getElementById('forward_config');
    parent.appendChild(DIV);
  }

  function toggleDisplay(checkbox_id, item_id) 
  {
    if (document.getElementById(checkbox_id).checked == true) {
      document.getElementById(item_id).style.display = 'block';
    } else {
      document.getElementById(item_id).style.display = 'none';
    }
  }

</script>
");

$form = '';

if ($accountlogin) {
  $form.= "<p class=\"spamfilter_options\">Unerwünschte E-Mails (Spam, Viren) in diesem Postfach ".html_select('spamfilter_action', array("none" => 'nicht filtern', "folder" => 'in Unterordner »Spam« ablegen', "tag" => 'markieren und zustellen', "delete" => 'nicht zustellen (löschen)'), $account['spamfilter'])."</p>";
} else {
  $form .= "
    <p><strong>E-Mail-Adresse:</strong>&#160;<input type=\"text\" name=\"local\" id=\"local\" size=\"10\" value=\"{$account['local']}\" /><strong style=\"font-size: 1.5em;\">&#160;@&#160;</strong>".domainselect($account['domain'])."</p>";

  $password_message = '';
  $password_value = '';
  if ($is_mailbox and ($account['password'] != ''))
  {
    $password_message = '<span style="font-size: 80%"><br /><em>Sie haben bereits ein Passwort gesetzt. Wenn Sie dieses Feld nicht ändern, wird das bisherige Passwort beibehalten.</em></span>';
    $password_value = '**********';
  } 
  
  $form .= "
    <p><input onchange=\"toggleDisplay('mailbox', 'mailbox_options')\" type=\"checkbox\" id=\"mailbox\" name=\"mailbox\" value=\"yes\" ".($is_mailbox ? 'checked="checked" ' : '')." /><label for=\"mailbox\">&#160;<strong>In Mailbox speichern</strong></label></p>
    <div style=\"margin-left: 2em;".($is_mailbox ? '' : ' display: none;')."\" id=\"mailbox_options\">
    <p>Passwort für Abruf:&#160;<input type=\"password\" id=\"password\" name=\"password\" value=\"{$password_value}\" />{$password_message}</p>";

  $form.= "<p class=\"spamfilter_options\">Unerwünschte E-Mails (Spam, Viren) in diesem Postfach ".html_select('spamfilter_action', array("none" => 'nicht filtern', "folder" => 'in Unterordner »Spam« ablegen', "tag" => 'markieren und zustellen', "delete" => 'nicht zustellen (löschen)'), $account['spamfilter'])."</p>";

  $quota = config('vmail_basequota');
  if ($is_mailbox and $account['quota']) {
    $quota = $account['quota'];
  }
  $form .= "<p class=\"quota_options\">Größe des Postfachs: <input type=\"text\" id=\"quota\" name=\"quota\" value=\"{$quota}\" /> MB<br /><span style=\"font-size: 80%\"><em>Hinweis: Die Differenz zwischen dem hier gesetzten Wert und dem Sockelbetrag von ".config('vmail_basequota')." MB wird vom Speicherplatz Ihres Benutzer-Kontos abgezogen.</em></span></p>";

  $quota_notify = ($account['quota_threshold'] >= 0) ? ' checked="checked" ' : '';
  $quota_threshold = ($account['quota_threshold'] >= 0) ? $account['quota_threshold'] : '';
  $form .= "<p class=\"quota_options\"><input type=\"checkbox\" id=\"quota_notify\" name=\"quota_notify\" value=\"1\" {$quota_notify} /><label for=\"quota_notify\">Benachrichtigung wenn weniger als</label> <input type=\"text\" name=\"quota_threshold\" id=\"quota_threshold\" value=\"{$quota_threshold}\" /> MB Speicherplatz zur Verfügung stehen.</p>";

  $form .= "</div>";
}



$form .= "<p><input onchange=\"toggleDisplay('autoresponder', 'autoresponder_config')\" type=\"checkbox\" id=\"autoresponder\" name=\"autoresponder\" value=\"yes\" ".($is_autoresponder ? 'checked="checked" ' : '')." /><label for=\"autoresponder\">&#160;<strong>Automatische Antwort versenden</strong></label></p>";

$form .= "<div style=\"margin-left: 2em;".($is_autoresponder ? '' : ' display: none;')."\" id=\"autoresponder_config\">";

$ar = $account['autoresponder'];
if (! $ar) {
  $ar = empty_autoresponder_config();
}

if ($ar['valid_until'] != NULL && $ar['valid_until'] < date('Y-m-d')) {
  // Daten sind Restbestand von einem früheren Einsatz des Autoresponders
  $ar['valid_from'] = NULL;
  $ar['valid_until'] = NULL;
}
$valid_from_now_checked = ($ar['valid_from'] <= date('Y-m-d H:i:s') || $ar['valid_from'] == NULL) ? ' checked="checked"' : '';
$valid_from_future_checked = ($ar['valid_from'] > date('Y-m-d H:i:s')) ? ' checked="checked"' : '';
$startdate = $ar['valid_from'];
if (! $startdate || $startdate <= date('Y-m-d')) {
  $startdate = date('Y-m-d', time() + 1*24*60*60);
}
$form .= "<p><input type=\"radio\" name=\"ar_valid_from\" value=\"now\" id=\"ar_valid_from_now\"{$valid_from_now_checked} /> <label for=\"ar_valid_from_now\">Ab sofort</label><br />".
  "<input type=\"radio\" name=\"ar_valid_from\" value=\"future\" id=\"ar_valid_from_future\"{$valid_from_future_checked} /> <label for=\"ar_valid_from_future\">Erst ab dem </label>".
  html_datepicker("ar_valid_from", strtotime($startdate))."</p>";

$valid_until_infinity_checked = ($ar['valid_until'] == NULL) ? ' checked="checked"' : '';
$valid_until_date_checked = ($ar['valid_until'] != NULL) ? ' checked="checked"' : '';
$enddate = $ar['valid_until'];
if (! $enddate) {
  $enddate = date('Y-m-d', time() + 7*24*60*60);
}
$form .= "<h4>Deaktivierung</h4>";
$form .= "<p><input type=\"radio\" name=\"ar_valid_until\" value=\"infinity\" id=\"ar_valid_until_infinity\"{$valid_until_infinity_checked} /> <label for=\"ar_valid_until_infinity\">Unbefristet</label><br />".
  "<input type=\"radio\" name=\"ar_valid_until\" value=\"date\" id=\"ar_valid_until_date\"{$valid_until_date_checked} /> <label for=\"ar_valid_until_date\">Keine Antworten mehr versenden ab dem </label>".
  html_datepicker("ar_valid_until", strtotime($enddate))."</p>";


$subject = $ar['subject'];
if ($subject == NULL)
  $subject = '';
$ar_subject_default_checked = ($subject == NULL) ? ' checked="checked"' : '';
$ar_subject_custom_checked = ($subject) ? ' checked="checked"' : '';
$form .= "<h4>Betreffzeile der automatischen Antwort</h4>".
  "<p><input type=\"radio\" name=\"ar_subject\" value=\"default\" id=\"ar_subject_default\"{$ar_subject_default_checked} /> ".
  "<label for=\"ar_subject_default\">Automatisch (Re: <em>&lt;Betreff der Originalnachricht&gt;</em>)</label><br />".
  "<input type=\"radio\" name=\"ar_subject\" value=\"custom\" id=\"ar_subject_custom\"{$ar_subject_custom_checked} /> ".
  "<label for=\"ar_subject_custom\">Anderer Betreff:</label> <input type=\"text\" name=\"ar_subject_value\" id=\"ar_subject_value\" value=\"{$subject}\"/></p>";

$message = $ar['message'];
$form .= "<h4>Inhalt der automatischen Antwort</h4>".
  "<p><textarea cols=\"80\" rows=\"10\" name=\"ar_message\" id=\"ar_message\">".$ar['message']."</textarea></p>";
$quote = $ar['quote'];
if (! $quote) 
  $quote = 'none';
$form .= "<p><label for=\"ar_quote\">Originalnachricht des Absenders </label>".
  html_select('ar_quote', array("none" => 'nicht in Antwort einschließen', 
                                "inline" => 'zitieren (max. 50 Zeilen)', 
                                "attach" => 'vollständig als Anhang beifügen'), $quote)."</p>";


$ar_from_default_checked = ($ar['fromname'] == NULL) ? ' checked="checked"' : '';
$ar_from_custom_checked = ($ar['fromname'] != NULL) ? ' checked="checked"' : '';
$fromname = $ar['fromname'];
$form .= "<h4>Absender der automatischen Antwort</h4>".
  "<p><input type=\"radio\" name=\"ar_from\" value=\"default\" id=\"ar_from_default\"{$ar_from_default_checked} /> <label for=\"ar_from_default\">Nur E-Mail-Adresse</label><br />".
  "<input type=\"radio\" name=\"ar_from\" value=\"custom\" id=\"ar_from_custom\"{$ar_from_custom_checked} /> <label for=\"ar_from_custom\">Mit Name: </label> ".
  "<input type=\"text\" name=\"ar_fromname\" id=\"ar_fromname\" value=\"{$fromname}\"/></p>";




$form .= '</div>';





$form .= "<p><input onchange=\"toggleDisplay('forward', 'forward_config')\" type=\"checkbox\" id=\"forward\" name=\"forward\" value=\"yes\" ".($is_forward ? 'checked="checked" ' : '')." /><label for=\"forward\">&#160;<strong>Weiterleitung an andere E-Mail-Adressen</strong></label></p>";


$form .= "<div style=\"margin-left: 2em;".($is_forward ? '' : ' display: none;')."\" id=\"forward_config\">";

if ($is_forward)
{
  for ($i = 0 ; $i < $numforwards ; $i++)
  {
    $num = $i+1;
    $form .= "<div class=\"vmail-forward\">
    <p>Weiterleiten an <input type=\"text\" id=\"forward_to_{$num}\" name=\"forward_to_{$num}\" value=\"{$account['forwards'][$i]['destination']}\" /></p>
    <p>Spam-Mails an diese Adresse ".html_select('spamfilter_action_'.$num, array("none" => 'nicht filtern', "tag" => 'markieren und zustellen', "delete" => 'nicht zustellen'), $account['forwards'][$i]['spamfilter'])."</p>
    </div>\n";
  }
}
else
{
    $form .= "<div class=\"vmail-forward\">
    <p>Weiterleiten an <input type=\"text\" id=\"forward_to_1\" name=\"forward_to_1\" value=\"\" /></p>
    <p>Spam-Mails an diese Adresse ".html_select('spamfilter_action_1', array("none" => 'nicht filtern', "tag" => 'markieren und zustellen', "delete" => 'nicht zustellen'), "none")."</p>
    </div>\n";
}

$form .= '<p>[ <a href="#" onclick="moreForward();">mehr Empfänger</a> ]</p>
</div>';

$form .= '<p><input type="submit" value="Speichern" />&#160;&#160;&#160;&#160;'.internal_link('vmail', 'Abbrechen').'</p>';

output(html_form('vmail_edit_mailbox', 'save', 'action=edit'.($id != 0 ? '&id='.$id : ''), $form));


?>
