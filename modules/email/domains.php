<?php
/*
This file belongs to the Webinterface of schokokeks.org Hosting

Written by schokokeks.org Hosting, namely
  Bernd Wurst <bernd@schokokeks.org>
  Hanno Böck <hanno@schokokeks.org>

This code is published under a 0BSD license.

Nevertheless, in case you use a significant part of this code, we ask (but not require, see the license) that you keep the authors' names in place and return your changes to the public. We would be especially happy if you tell us what you're going to do with this code.
*/

require_once('inc/base.php');
require_once('inc/security.php');
require_role(ROLE_SYSTEMUSER);
require_once('inc/javascript.php');
javascript('domains.js');

require_once('vmail.php');

$settings = domainsettings();

$domains = $settings['domains'];
$subdomains = $settings['subdomains'];

DEBUG($settings);

title("E-Mail-Verwaltung");
output('<p>Sie können bei ' . config('company_name') . ' die E-Mails Ihrer Domains auf zwei unterschiedliche Arten empfangen.</p>
<ol><li>Sie können einfache E-Mail-Konten erstellen, die ankommende E-Mails speichern oder weiterleiten.</li>
<li>Sie können die manuelle Verwaltung wählen, bei der Sie passende .courier-Dateien für den Empfang und
manuelle POP3/IMAP-Konten für den Abruf erstellen können.</li></ol>
<p>Diese Wahlmöglichkeit haben Sie pro Domain bzw. Subdomain. Eine parallele Nutzung beider Verfahren ist nicht möglich.
Wenn Sie eine Domain auf Webinterface-Verwaltung einrichten, dann werden eventuell vorhandene .courier-Dateien nicht mehr 
beachtet. Subdomains können grundsätzlich nur durch Administratoren eingerichtet und verändert werden.</p>

<p><strong>DKIM:</strong> Für jede Domain können Sie zudem einstellen, ob die ausgehenden Mails eine DKIM-Signatur bekommen 
sollen bzw. ob zusätzlich eine DMARC-Policy veröffentlicht werden soll.</p>

<p class="warning"><strong>BITTE BEACHTEN:</strong> Vor der Aktivierung von DMARC beachten Sie bitte die <a href="https://wiki.schokokeks.org/E-Mail/DKIM">Informationen zu DKIM / DMARC</a>, insbesondere den Abschnitt "Mails von PHP-Applikationen und anderen serverseitigen Anwendungen".</p>

<h4>Ihre Domains sind momentan wie folgt konfiguriert:</h4>

<table>
  <tr><th>Domainname</th><th>Einstellung</th><th></th><th></th></tr>
');

$odd = true;
foreach ($domains as $id => $dom) {
    $odd = !$odd;
    $trextra = ($odd ? ' class="odd"' : ' class="even"');
    $edit_disabled = false;
    $notice = '';
    if ($dom['type'] == 'manual') {
        $edit_disabled = true;
        $notice = 'Kann nur von Admins geändert werden';
    }
    if (domain_has_vmail_accounts($id)) {
        $edit_disabled = true;
        $notice = 'Keine Änderung möglich, solange noch ' . internal_link("vmail", "E-Mail-Konten") . ' für diese Domain eingerichtet sind.';
    }
    if ($dom['mailserver_lock']) {
        $trextra .= ' style="background-color: #faa;"';
        $notice .= ' <strong>Mailserver-Sperre aktiv!</strong>';
    }
    $check_nomail = ($dom['type'] == 'nomail' ? ' checked="checked"' : '');
    $check_off = ($dom['type'] == 'none' ? ' checked="checked"' : '');
    $check_webinterface = ($dom['type'] == 'virtual' ? ' checked="checked"' : '');
    $check_manual = ($dom['type'] == 'auto' || $dom['type'] == 'manual' ? ' checked="checked"' : '');

    $buttons = '<span class="buttonset' . ($edit_disabled ? ' disabled' : '') . '" id="buttonset-' . $id . '">
         <input type="radio" name="option-' . $id . '" id="option-' . $id . '-webinterface" value="webinterface"' . $check_webinterface . ' ' . ($edit_disabled ? ' disabled="disabled"' : '') . '/>
         <label for="option-' . $id . '-webinterface">Webinterface</label>
         <input type="radio" name="option-' . $id . '" id="option-' . $id . '-manual" value="manual"' . $check_manual . ' ' . ($edit_disabled ? ' disabled="disabled"' : '') . '/>
         <label for="option-' . $id . '-manual">Manuell</label>
         <input type="radio" name="option-' . $id . '" id="option-' . $id . '-off" value="off"' . $check_off . ' ' . ($edit_disabled ? ' disabled="disabled"' : '') . '/>
         <label for="option-' . $id . '-off">Ausgeschaltet</label>';
    if ($dom['type'] == 'nomail' || $dom['type'] == 'none') {
        $buttons .= '<input type="radio" class="nomail" name="option-' . $id . '" id="option-' . $id . '-nomail" value="nomail"' . $check_nomail . ' ' . ($edit_disabled ? ' disabled="disabled"' : '') . '/>
                     <label class="nomail" for="option-' . $id . '-nomail">Mail-Nutzung verhindern</label>';
    }
    $buttons .= '<input type="submit" value="Speichern" />
      </span>';

    if ($dom['type'] != 'none' && $dom['type'] != 'nomail' && $dom['dns'] == 1) {
        $check_dmarc = ($dom['dkim'] == 'dmarc' ? ' checked="checked"' : '');
        $check_dkim = ($dom['dkim'] == 'dkim' ? ' checked="checked"' : '');
        $check_dkimoff = ($dom['dkim'] == 'none' ? ' checked="checked"' : '');
        $buttons .= '&nbsp;<select name="dkim-' . $id . '" id="dkim-select-' . $id . '" class="autosubmit">
            <option value="dmarc" ' . ($dom['dkim'] == 'dmarc' ? 'selected' : '') . '>DKIM + DMARC</option>
            <option value="dkim" ' . ($dom['dkim'] == 'dkim' ? 'selected' : '') . '>Nur DKIM</option>
            <option value="none" ' . ($dom['dkim'] == 'none' ? 'selected' : '') . '>DKIM ausgeschaltet</option>
        </select>
         <input class="hidden" type="submit" value="Speichern" />
      ';
    } else {
        //$buttons .= 'Sie können keine DKIM-Einstellung vornehmen, wenn der Mail-Empfang ausgeschaltet ist.';
    }
    output("<tr{$trextra}><td>{$dom['name']}</td><td class=\"nowrap\">" . html_form('vmail_domainchange', 'domainchange', '', $buttons) . "</td><td>{$notice}</td></tr>\n");
    if (array_key_exists($id, $subdomains)) {
        foreach ($subdomains[$id] as $subdom) {
            $odd = !$odd;
            $trextra = ($odd ? ' class="odd"' : ' class="even"');
            $edit_disabled = true;
            $check_webinterface = ($subdom['type'] == 'virtual' ? ' checked="checked"' : '');
            $check_manual = ($subdom['type'] == 'auto' || $subdom['type'] == 'manual' ? ' checked="checked"' : '');
            $id = $id . '-' . $subdom['name'];
            $buttons = '<span class="buttonset' . ($edit_disabled ? ' disabled' : '') . '" id="buttonset-' . $id . '">
         <input type="radio" name="option-' . $id . '" id="option-' . $id . '-webinterface" value="webinterface"' . $check_webinterface . ' ' . ($edit_disabled ? ' disabled="disabled"' : '') . '/>
         <label for="option-' . $id . '-webinterface">Webinterface</label>
         <input type="radio" name="option-' . $id . '" id="option-' . $id . '-manual" value="manual"' . $check_manual . ' ' . ($edit_disabled ? ' disabled="disabled"' : '') . '/>
         <label for="option-' . $id . '-manual">Manuell</label>
         <input type="radio" name="option-' . $id . '" id="option-' . $id . '-off" value="off"' . ($edit_disabled ? ' disabled="disabled"' : '') . '/>
         <label for="option-' . $id . '-off">Ausgeschaltet</label>
      </span>';
            output("<tr{$trextra}><td>{$subdom['name']}.{$dom['name']}</td><td>{$buttons}</td><td>Subdomains können nur von Admins geändert werden!</td></tr>\n");
        }
    }
}
output('</table>
<br />');

output('<p><strong>Sicherheitshinweis:</strong> Während der Umstellung der Empfangsart ist Ihre Domain eventuell für einige Minuten in einem undefinierten Zustand. In dieser Zeit kann es passieren, dass E-Mails nicht korrekt zugestellt oder sogar ganz zurückgewiesen werden. Sie sollten diese Einstellungen daher nicht mehr ändern, wenn die Domain aktiv für den E-Mail-Verkehr benutzt wird.</p>
');
