<?php
/*
This file belongs to the Webinterface of schokokeks.org Hosting

Written 2008-2018 by schokokeks.org Hosting, namely
  Bernd Wurst <bernd@schokokeks.org>
  Hanno Böck <hanno@schokokeks.org>

To the extent possible under law, the author(s) have dedicated all copyright and related and neighboring rights to this software to the public domain worldwide. This software is distributed without any warranty.

You should have received a copy of the CC0 Public Domain Dedication along with this software. If not, see 
http://creativecommons.org/publicdomain/zero/1.0/

Nevertheless, in case you use a significant part of this code, we ask (but not require, see the license) that you keep the authors' names in place and return your changes to the public. We would be especially happy if you tell us what you're going to do with this code.
*/

require_once('inc/security.php');
require_once('inc/icons.php');
require_once('inc/jquery.php');
javascript();

require_once('domainapi.php');
require_once('domains.php');

require_role(ROLE_CUSTOMER);

title("Domain hinzufügen");
$section='domains_domains';

output('<p>Mit dieser Funktion können Sie eine neue Domain bestellen oder eine bestehende, extern registrierte Domain verfügbar machen.</p>');

$form = '<p>Domainname: <input type="text" name="domain" size="50" /> <input type="submit" value="Verfügbarkeit prüfen" />';

output(html_form('adddomain_search', '', '', $form));

if (isset($_REQUEST['domain'])) {
    if (strpos($_REQUEST['domain'], ' ') !== false) {
        warning('Leerzeichen sind in Domainnamen nicht erlaubt.');
        redirect('');
    }
    $dom = new Domain();
    if ($dom->loadByName($_REQUEST['domain']) !== false) {
        if ($dom->is_customerdomain()) {
            warning('Diese Domain ist bereits in Ihrem Kundenkonto eingetragen!');
        } else {
            warning('Diese Domain ist bei einem anderen Kunden von uns in Nutzung. Kontaktieren Sie den Support, wenn Sie eine Domain in ein anderes Kundenkonto übertragen möchten.');
        }
        redirect('');
    }
    $avail = api_domain_available($_REQUEST['domain']);
    if ($avail == 'available') {
        output('<p class="domain-available">Die Domain '.filter_input_general($_REQUEST['domain']).' ist verfügbar!</p>');
        # Neue Domain eintragen
        $data = get_domain_offer($_REQUEST['domain']);
        if (!$data) {
            redirect('');
        }
        $form = '<p>Folgende Konditionen gelten bei Registrierung der Domain im nächsten Schritt:</p>
            <table>
            <tr><td>Domainname:</td><td><strong>'.$data['domainname'].'</strong></td></tr>
            <tr><td>Jahresgebühr:</td><td style="text-align: right;">'.$data['gebuehr'].' €</td></tr>
            <tr><td>Setup-Gebühr (einmalig):</td><td style="text-align: right;">'.$data['setup'].' €</td></tr>';
        $users = list_useraccounts();
        if (count($users) > 1) {
            $userselect = array();
            foreach ($users as $u) {
                $userselect[$u['uid']] = $u['username'].' / '.$u['name'];
            }


            $form .= '<tr><td>Benutzeraccount:</td><td>'.html_select('uid', $userselect).'</td></tr>';
        }
        $form .='</table>';


        $form .= '<p><input type="hidden" name="domain" value="'.filter_input_general($_REQUEST['domain']).'">
            <input type="submit" name="submit" value="Ich möchte diese Domain registrieren"></p>';
        output(html_form('domains_register', 'domainreg', '', $form));
        output('<p>'.internal_link('domains', 'Zurück').'</p>');
    } elseif ($avail == 'registered' || $avail == 'alreadyRegistered') {
        output('<p class="domain-unavailable">Die Domain '.filter_input_general($_REQUEST['domain']).' ist bereits vergeben.</p>');
        
        output('<h3>Domain zu '.config('company_name').' umziehen</h3>');
        $data = get_domain_offer($_REQUEST['domain']);

        if (! $data) {
            // Die Include-Datei setzt eine passende Warning-Nachricht
            output('<p>Eine Registrierung ist nicht automatisiert möglich. Bitte wenden Sie sich an den Support.');
        } else {

            $form = '<p>Folgende Konditionen gelten beim Transfer der Domain im nächsten Schritt:</p>
                <table>
                <tr><td>Domainname:</td><td><strong>'.$data['domainname'].'</strong></td></tr>
                <tr><td>Jahresgebühr:</td><td style="text-align: right;">'.$data['gebuehr'].' €</td></tr>
                <tr><td>Setup-Gebühr (einmalig):</td><td style="text-align: right;">'.$data['setup'].' €</td></tr>';
            $form .='</table>';


            $form .= '<p><input type="hidden" name="domain" value="'.filter_input_general($_REQUEST['domain']).'">
                <input type="submit" name="submit" value="Ich möchte diese Domain zu '.config('company_name').' umziehen"></p>';

            output(html_form('domains_transferin', 'domainreg', '', $form));

        }
        output('<h3>Diese Domain als externe Domain nutzen</h3>');
        output('<p>Sie können diese Domain für Konfigurationen bei uns nutzen ohne einen Transfer vorzunehmen.</p>
        <p><strong>Beachten Sie:</strong> Um diese Domain nutzen zu können, benötigen Sie bei Ihrem bisherigen Domainregistrar die Möglichkeit, DNS-Records anzulegen oder die zuständigen DNS-Server zu ändern. Sie können dann entweder unsere DNS-Server nutzen oder einzelne DNS-Records auf unsere Server einrichten.</p>');

        output('<p>Mit Betätigen des unten stehenden Knopfes bestätigen Sie, dass Sie entweder der Domaininhaber sind oder mit expliziter Zustimmung des Domaininhabers handeln.</p>');
        $form = '
            <p class="buttonset" id="buttonset-external">
            <input type="radio" name="dns" id="option-dns-enable" value="enable" />
            <label for="option-dns-enable">Lokalen DNS-Server aktivieren</label>
            <input type="radio" name="dns" id="option-dns-disable" value="disable" checked="checked" />
            <label for="option-dns-disable">Weiterhin externen DNS verwenden</label>
            </p>

            <p class="buttonset" id="buttonset-email">
            <input type="radio" name="email" id="option-email-enable" value="enable" checked="checked" />
            <label for="option-email-enable">E-Mail-Nutzung aktivieren</label>
            <input type="radio" name="email" id="option-email-disable" value="disable" />
            <label for="option-email-disable">Nicht für E-Mail nutzen</label>
            </p>';

        $form .= '<p><input type="hidden" name="domain" value="'.filter_input_general($_REQUEST['domain']).'">
            <input type="submit" name="submit" value="Diese Domain bei '.config('company_name').' verwenden"></p>';

        output(html_form('domains_external', 'useexternal', '', $form));
        output('</div>');

    } else {
        output('<p class="domain-unavailable">Die Domain '.filter_input_general($_REQUEST['domain']).' kann nicht registriert werden.</p>');

        switch ($avail) {
            case 'nameContainsForbiddenCharacter':
                output('<p>Der Domainname enthält unerlaubte Zeichen.</p>');
                break;
            case 'extensionDoesNotExist':
            case 'extensionCannotBeRegistered':
            case 'suffixDoesNotExist':
            case 'suffixCannotBeRegistered':
                output('<p>Diese Endung ist nicht verfügbar.</p>');
                break;
            default:
                output('<p>Ein Fehler ist aufgetreten beim Prüfen der Verfügbarkeit. Eventuell geht es später wieder.</p>');

        }
    }

}
