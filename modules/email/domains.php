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
output('<p>Sie können bei '.config('company_name').' die E-Mails Ihrer Domains auf zwei unterschiedliche Arten empfangen.</p>
<ol><li>Sie können einfache E-Mail-Konten erstellen, die ankommende E-Mails speichern oder weiterleiten.</li>
<li>Sie können die manuelle Verwaltung wählen, bei der Sie passende .courier-Dateien für den Empfang und
manuelle POP3/IMAP-Konten für den Abruf erstellen können.</li></ol>
<p>Diese Wahlmöglichkeit haben Sie pro Domain bzw. Subdomain. Eine parallel Nutzung beider Verfahren ist nicht möglich.
Wenn Sie eine Domain auf Webinterface-Verwaltung einrichten, dann werden eventuell vorhandene .courier-Dateien nicht mehr 
beachtet. Subdomains können grundsätzlich nur durch Administratoren eingerichtet und verändert werden.</p>

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
        $notice = 'Keine Änderung möglich, so lange noch '.internal_link("vmail", "E-Mail-Konten").' für diese Domain eingerichtet sind.';
    }
    if ($dom['mailserver_lock']) {
        $trextra .= ' style="background-color: #faa;"';
        $notice .= ' <strong>Mailserver-Sperre aktiv!</strong>';
    }
    $check_off = ($dom['type'] == 'none' ? ' checked="checked"' : '');
    $check_webinterface = ($dom['type'] == 'virtual' ? ' checked="checked"' : '');
    $check_manual = ($dom['type'] == 'auto' || $dom['type'] == 'manual' ? ' checked="checked"' : '');

    $buttons = '<span class="buttonset'.($edit_disabled ? ' disabled' : '').'" id="buttonset-'.$id.'">
         <input type="radio" name="option-'.$id.'" id="option-'.$id.'-webinterface" value="webinterface"'.$check_webinterface.' '.($edit_disabled ? ' disabled="disabled"' : '').'/>
         <label for="option-'.$id.'-webinterface">Webinterface</label>
         <input type="radio" name="option-'.$id.'" id="option-'.$id.'-manual" value="manual"'.$check_manual.' '.($edit_disabled ? ' disabled="disabled"' : '').'/>
         <label for="option-'.$id.'-manual">Manuell</label>
         <input type="radio" name="option-'.$id.'" id="option-'.$id.'-off" value="off"'.$check_off.' '.($edit_disabled ? ' disabled="disabled"' : '').'/>
         <label for="option-'.$id.'-off">Ausgeschaltet</label>
         <input type="submit" value="Speichern" />
      </span>';
    
    if ($dom['type'] != 'none') {
        $check_dmarc = ($dom['dkim'] == 'dmarc' ? ' checked="checked"' : '');
        $check_dkim = ($dom['dkim'] == 'dkim' ? ' checked="checked"' : '');
        $check_dkimoff = ($dom['dkim'] == 'none' ? ' checked="checked"' : '');
        $buttons .= ' <p><label for="dkim-select">DKIM-Einstellung: </label><select name="dkim" id="dkim-select">
            <option value="dmarc" '.($dom['dkim'] == 'dmarc' ? 'selected' : '').'>DKIM + DMARC</option>
            <option value="dkim" '.($dom['dkim'] == 'dkim' ? 'selected' : '').'>Nur DKIM</option>
            <option value="none" '.($dom['dkim'] == 'none' ? 'selected' : '').'>DKIM ausgeschaltet</option>
        </select></p>
        <p>Werden E-Mails nur über unsere Anlagen versendet oder nutzen Sie für den Versand unter dieser Domain auch andere Anbieter?</p>
        <p><span class="buttonset" id="buttonset-dkim-'.$id.'">
         <input type="radio" name="dkim-'.$id.'" id="dkim-'.$id.'-dmarc" value="dmarc"'.$check_dmarc.' />
         <label for="dkim-'.$id.'-dmarc">Nur schokokeks.org</label>
         <input type="radio" name="dkim-'.$id.'" id="dkim-'.$id.'-dkim" value="dkim"'.$check_dkim.' />
         <label for="dkim-'.$id.'-dkim">Auch andere Anbieter</label>
         <input type="radio" name="dkim-'.$id.'" id="dkim-'.$id.'-off" value="off"'.$check_dkimoff.' />
         <label for="dkim-'.$id.'-off">DKIM Ausgeschaltet</label>
         <input type="submit" value="Speichern" />
      </span>';

    }
    output("<tr{$trextra}><td>{$dom['name']}</td><td>".html_form('vmail_domainchange', 'domainchange', '', $buttons)."</td><td>{$notice}</td></tr>\n");
    if (array_key_exists($id, $subdomains)) {
        foreach ($subdomains[$id] as $subdom) {
            $odd = !$odd;
            $trextra = ($odd ? ' class="odd"' : ' class="even"');
            $edit_disabled = true;
            $check_webinterface = ($subdom['type'] == 'virtual' ? ' checked="checked"' : '');
            $check_manual = ($subdom['type'] == 'auto' || $subdom['type'] == 'manual' ? ' checked="checked"' : '');
            $id = $id.'-'.$subdom['name'];
            $buttons = '<span class="buttonset'.($edit_disabled ? ' disabled' : '').'" id="buttonset-'.$id.'">
         <input type="radio" name="option-'.$id.'" id="option-'.$id.'-webinterface" value="webinterface"'.$check_webinterface.' '.($edit_disabled ? ' disabled="disabled"' : '').'/>
         <label for="option-'.$id.'-webinterface">Webinterface</label>
         <input type="radio" name="option-'.$id.'" id="option-'.$id.'-manual" value="manual"'.$check_manual.' '.($edit_disabled ? ' disabled="disabled"' : '').'/>
         <label for="option-'.$id.'-manual">Manuell</label>
         <input type="radio" name="option-'.$id.'" id="option-'.$id.'-off" value="off"'.($edit_disabled ? ' disabled="disabled"' : '').'/>
         <label for="option-'.$id.'-off">Ausgeschaltet</label>
      </span>';
            output("<tr{$trextra}><td>{$subdom['name']}.{$dom['name']}</td><td>{$buttons}</td><td>Subdomains können nur von Admins geändert werden!</td></tr>\n");
        }
    }
}
output('</table>
<br />');

output('<p><strong>Sicherheitshinweis:</strong> Während der Umstellung der Empfangsart ist Ihre Domain eventuell für einige Minuten in einem undefinierten Zustand. In dieser Zeit kann es passieren, dass E-Mails nicht korrekt zugestellt oder sogar ganz zurückgewiesen werden. Sie sollten diese Einstellungen daher nicht mehr ändern, wenn die Domain aktiv für den E-Mail-Verkehr benutzt wird.</p>
');
