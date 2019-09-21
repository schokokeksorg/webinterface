<?php
/*
This file belongs to the Webinterface of schokokeks.org Hosting

Written 2008-2018 by schokokeks.org Hosting, namely
  Bernd Wurst <bernd@schokokeks.org>
  Hanno Böck <hanno@schokokeks.org>

To the extent possible under law, the author(s) have dedicated all copyright and related and neighboring rights to this software to the public domain worldwide. This software is distributed without any warranty.

You should have received a copy of the CC0 Public Domain Dedication along with this software. If not, see
https://creativecommons.org/publicdomain/zero/1.0/

Nevertheless, in case you use a significant part of this code, we ask (but not require, see the license) that you keep the authors' names in place and return your changes to the public. We would be especially happy if you tell us what you're going to do with this code.
*/

require_once('inc/debug.php');
require_once('inc/security.php');
require_once('inc/icons.php');

require_once('vmail.php');

require_once('inc/jquery.php');
javascript();

$section = 'email_vmail';
require_role(array(ROLE_SYSTEMUSER, ROLE_VMAIL_ACCOUNT));

$account = empty_account();
$id = (isset($_GET['id']) ? (int) $_GET['id'] : 0);

if ($id != 0) {
    $account = get_account_details($id);
}

$accountlogin = false;
if ($_SESSION['role'] == ROLE_VMAIL_ACCOUNT) {
    $section = 'email_edit';
    $id = get_vmail_id_by_emailaddr($_SESSION['mailaccount']);
    $account = get_account_details($id, false);
    $accountlogin = true;
    $accountname = filter_output_html($_SESSION['mailaccount']);
}


DEBUG($account);
if ($id == 0) {
    title("E-Mail-Adresse anlegen");
} else {
    if ($accountlogin) {
        title("Einstellungen für {$accountname}");
    } else {
        title("E-Mail-Adresse bearbeiten");
    }
}


$is_autoresponder = is_array($account['autoresponder']) && $account['autoresponder']['valid_from'] != null && ($account['autoresponder']['valid_until'] > date('Y-m-d') || $account['autoresponder']['valid_until'] == null);
$is_forward = (count($account['forwards']) > 0);
$is_mailbox = ($account['password'] != null  ||  $id == 0);
$numforwards = max(count($account['forwards']), 1);

$form = '';

if (! $accountlogin) {
    if ($id != 0) {
        $domainlist = get_vmail_domains();
        $domain = null;
        foreach ($domainlist as $dom) {
            if ($dom['id'] == $account['domain']) {
                $domain = $dom['domainname'];
            }
        }
        $form .= "
    <p><strong style=\"font-size: 1.5em;\">{$account['local']}@{$domain}</strong></p>";
    } else {
        $domain = null;
        if (isset($_GET['domain'])) {
            $domain = (int) $_GET['domain'];
        }
        $form .= "
    <p><strong>E-Mail-Adresse:</strong>&#160;<input type=\"text\" name=\"local\" id=\"local\" size=\"10\" value=\"".filter_output_html($account['local'])."\" /><strong style=\"font-size: 1.5em;\">&#160;@&#160;</strong>".domainselect($domain)."</p>";
    }
    $password_message = '';
    $password_value = '';
    if ($is_mailbox and ($account['password'] != '')) {
        $password_message = '<span style="font-size: 80%"><br /><em>Sie haben bereits ein Passwort gesetzt. Wenn Sie dieses Feld nicht ändern, wird das bisherige Passwort beibehalten.</em></span>';
        $password_value = '**********';
    }

    $form .= "
    <p><input class=\"option_group\" type=\"checkbox\" id=\"mailbox\" name=\"mailbox\" value=\"yes\" ".($is_mailbox ? 'checked="checked" ' : '')." /><label for=\"mailbox\">&#160;<strong>In Mailbox speichern</strong></label></p>
    <div style=\"margin-left: 2em;\" id=\"mailbox_config\" class=\"option_group\">
    <p>Passwort für Abruf:&#160;<input style=\"color: #aaa;\" type=\"password\" id=\"password\" name=\"password\" value=\"{$password_value}\" />{$password_message}</p>";

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



$form .= "<p><input class=\"option_group\" type=\"checkbox\" id=\"autoresponder\" name=\"autoresponder\" value=\"yes\" ".($is_autoresponder ? 'checked="checked" ' : '')." /><label for=\"autoresponder\">&#160;<strong>Automatische Antwort versenden</strong></label></p>";

$form .= "<div style=\"margin-left: 2em;\" id=\"autoresponder_config\" class=\"option_group\">";

$ar = $account['autoresponder'];
if (! $ar) {
    $ar = empty_autoresponder_config();
}

if ($ar['valid_until'] != null && $ar['valid_until'] < date('Y-m-d')) {
    // Daten sind Restbestand von einem früheren Einsatz des Autoresponders
    $ar['valid_from'] = null;
    $ar['valid_until'] = null;
}
$valid_from_now_checked = ($ar['valid_from'] <= date('Y-m-d H:i:s') || $ar['valid_from'] == null) ? ' checked="checked"' : '';
$valid_from_future_checked = ($ar['valid_from'] > date('Y-m-d H:i:s')) ? ' checked="checked"' : '';
$startdate = $ar['valid_from'];
if (! $startdate || $startdate <= date('Y-m-d')) {
    $startdate = date('Y-m-d', time() + 1*24*60*60);
}
$form .= "<p><input type=\"radio\" name=\"ar_valid_from\" value=\"now\" id=\"ar_valid_from_now\"{$valid_from_now_checked} /> <label for=\"ar_valid_from_now\">Ab sofort</label><br />".
  "<input type=\"radio\" name=\"ar_valid_from\" value=\"date\" id=\"ar_valid_from_date\"{$valid_from_future_checked} /> <label for=\"ar_valid_from_date\">Erst ab dem </label>".
  "<input type=\"text\" value=\"$startdate\" id=\"ar_startdate\" name=\"ar_startdate\" /></p>";

$valid_until_infinity_checked = ($ar['valid_until'] == null) ? ' checked="checked"' : '';
$valid_until_date_checked = ($ar['valid_until'] != null) ? ' checked="checked"' : '';
$enddate = $ar['valid_until'];
if (! $enddate) {
    $enddate = date('Y-m-d', time() + 7*24*60*60);
}
$form .= "<h4>Deaktivierung</h4>";
$form .= "<p><label for=\"ar_valid_until_date\">Keine Antworten mehr versenden ab dem </label>".
  "<input type=\"text\" value=\"$enddate\" id=\"ar_enddate\" name=\"ar_enddate\" /><br/>";
if (!$accountlogin && ($id != 0)) {
    $form .= "<small>(Automatische Antworten sind nur befristet erlaubt. Wenn Sie diese Adresse dauerhaft stilllegen möchten, können Sie dies am Ende dieser Seite tun.)</small></p>";
}
/*
$form .= "<p><input type=\"radio\" name=\"ar_valid_until\" value=\"infinity\" id=\"ar_valid_until_infinity\"{$valid_until_infinity_checked} /> <label for=\"ar_valid_until_infinity\">Unbefristet</label><br />".
  "<input type=\"radio\" name=\"ar_valid_until\" value=\"date\" id=\"ar_valid_until_date\"{$valid_until_date_checked} /> <label for=\"ar_valid_until_date\">Keine Antworten mehr versenden ab dem </label>".
  "<input type=\"text\" value=\"$enddate\" id=\"ar_enddate\" name=\"ar_enddate\" /><br/><small>(Automatische Antworten sind nur befristet erlaubt. Benötigen Sie langfristig funktionierende automatische Antworten, sprechen Sie unsere Administratoren bitte an, dann suchen wir eine Lösung.)</small></p>";
*/

$subject = filter_output_html($ar['subject']);
$ar_subject_default_checked = ($subject == null) ? ' checked="checked"' : '';
$ar_subject_custom_checked = ($subject) ? ' checked="checked"' : '';
$form .= "<h4>Betreffzeile der automatischen Antwort</h4>".
  "<p><input type=\"radio\" name=\"ar_subject\" value=\"default\" id=\"ar_subject_default\"{$ar_subject_default_checked} /> ".
  "<label for=\"ar_subject_default\">Automatisch (Re: <em>&lt;Betreff der Originalnachricht&gt;</em>)</label><br />".
  "<input type=\"radio\" name=\"ar_subject\" value=\"custom\" id=\"ar_subject_custom\"{$ar_subject_custom_checked} /> ".
  "<label for=\"ar_subject_custom\">Anderer Betreff:</label> <input type=\"text\" name=\"ar_subject_value\" id=\"ar_subject_value\" value=\"{$subject}\"/></p>";

$message = filter_output_html($ar['message']);
$form .= "<h4>Inhalt der automatischen Antwort</h4>".
  "<p><textarea cols=\"80\" rows=\"10\" name=\"ar_message\" id=\"ar_message\">{$message}</textarea></p>";
$quote = $ar['quote'];
if (! $quote) {
    $quote = 'none';
}
$form .= "<p><label for=\"ar_quote\">Originalnachricht des Absenders </label>".
  html_select('ar_quote', array("none" => 'nicht in Antwort einschließen',
                                "teaser" => 'anreißen (erste 10 Zeilen)',
                                "inline" => 'zitieren (max. 50 Zeilen)'), $quote)."</p>";
                                //"attach" => 'vollständig als Anhang beifügen'), $quote)."</p>";


$ar_from_default_checked = ($ar['fromname'] == null) ? ' checked="checked"' : '';
$ar_from_custom_checked = ($ar['fromname'] != null) ? ' checked="checked"' : '';
$fromname = filter_output_html($ar['fromname']);
$form .= "<h4>Absender der automatischen Antwort</h4>".
  "<p><input type=\"radio\" name=\"ar_from\" value=\"default\" id=\"ar_from_default\"{$ar_from_default_checked} /> <label for=\"ar_from_default\">Nur E-Mail-Adresse</label><br />".
  "<input type=\"radio\" name=\"ar_from\" value=\"custom\" id=\"ar_from_custom\"{$ar_from_custom_checked} /> <label for=\"ar_from_custom\">Mit Name: </label> ".
  "<input type=\"text\" name=\"ar_fromname\" id=\"ar_fromname\" value=\"{$fromname}\"/></p>";




$form .= '</div>';





$form .= "<p><input class=\"option_group\" type=\"checkbox\" id=\"forward\" name=\"forward\" value=\"yes\" ".($is_forward ? 'checked="checked" ' : '')." /><label for=\"forward\">&#160;<strong>Weiterleitung an andere E-Mail-Adressen</strong></label></p>";


$form .= "<div style=\"margin-left: 2em;\" id=\"forward_config\" class=\"option_group\">";

$form .= '<div id="forward_entries">
';
if (! isset($account['forwards'][0])) {
    $account['forwards'][0] = array('destination' => '');
}
while (count($account['forwards']) < 10) {
    // Dummy-Einträge für Leute ohne JavaScript
    $account['forwards'][] = array('destination' => '');
}
for ($i = 0 ; $i < max($numforwards, 10) ; $i++) {
    $num = $i+1;
    $form .= "<div class=\"vmail-forward\" id=\"vmail_forward_{$num}\">
  <div style=\"float: right;\" class=\"delete_forward\">".icon_delete("Diese Weiterleitung entfernen")."</div>
  <p>Weiterleiten an <input type=\"text\" id=\"forward_to_{$num}\" name=\"forward_to_{$num}\" value=\"".filter_output_html($account['forwards'][$i]['destination'])."\" /></p>
  </div>\n";
}
$form .= '</div>';

$form .= '<p><a href="#" id="more_forwards">'.icon_add().' Weiteren Empfänger hinzufügen</a></p>
</div>';

$target = 'vmail';
if ($accountlogin) {
    $target = '../index/index';
}
$form .= '<p><input id="submit" type="submit" value="Speichern" />&#160;&#160;&#160;&#160;'.internal_link($target, 'Abbrechen').'</p>';

output(html_form('vmail_edit_mailbox', 'save', 'action=edit'.($id != 0 ? '&id='.$id : ''), $form));

if (! $accountlogin && ($id != 0)) {
    output("<p>".internal_link('suspend', 'Diese Adresse stilllegen (mit individuellem Fehlertext)', "account=".$id)."</p>");
}
