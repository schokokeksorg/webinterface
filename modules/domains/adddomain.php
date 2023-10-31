<?php
/*
This file belongs to the Webinterface of schokokeks.org Hosting

Written by schokokeks.org Hosting, namely
  Bernd Wurst <bernd@schokokeks.org>
  Hanno Böck <hanno@schokokeks.org>

This code is published under a 0BSD license.

Nevertheless, in case you use a significant part of this code, we ask (but not require, see the license) that you keep the authors' names in place and return your changes to the public. We would be especially happy if you tell us what you're going to do with this code.
*/

require_once('inc/security.php');
require_once('inc/icons.php');

require_once('domainapi.php');
require_once('domains.php');

require_role(ROLE_CUSTOMER);
if (!config('http.net-apikey')) {
    system_failure("Dieses System ist nicht eingerichtet zum Hinzufügen von Domains");
}

title("Domain hinzufügen");
$section = 'domains_domains';

output('<p>Mit dieser Funktion können Sie eine neue Domain bestellen oder eine bestehende, extern registrierte Domain verfügbar machen.</p>');

$form = '<p>Domainname: <input type="text" name="domain" size="50" /> <input type="submit" value="Verfügbarkeit prüfen" />';

output(html_form('adddomain_search', '', '', $form));

if (isset($_REQUEST['domain'])) {
    $request = idn_to_utf8(trim($_REQUEST['domain']), 0, INTL_IDNA_VARIANT_UTS46);
    if (substr($request, 0, 4) == 'www.') {
        $request = str_replace('www.', '', $request);
    }
    verify_input_hostname_utf8($request);
    $punycode = idn_to_ascii($request, 0, INTL_IDNA_VARIANT_UTS46);
    if (!check_domain($punycode)) {
        warning("Ungültiger Domainname: " . filter_output_html($request));
        redirect('');
    }
    $dom = new Domain();
    if ($dom->loadByName($request) !== false) {
        if ($dom->is_customerdomain()) {
            warning('Diese Domain ist bereits in Ihrem Kundenkonto eingetragen!');
        } else {
            warning('Diese Domain ist bei einem anderen Kunden von uns in Nutzung. Kontaktieren Sie den Support, wenn Sie eine Domain in ein anderes Kundenkonto übertragen möchten.');
        }
        redirect('');
    }
    $avail = api_domain_available($request);
    if ($avail['status'] == 'available') {
        output('<p class="domain-available">Die Domain ' . filter_output_html($request) . ' ist verfügbar!</p>');
        # Neue Domain eintragen
        $data = get_domain_offer($avail['domainSuffix']);
        if ($data === false) {
            output('<p>Diese Endung ist für die automatische Registrierung nicht freigeschaltet. Bitte fragen Sie bei unserem Support nach den Konditionen und Bedingungen für diese Domain-Endung!</p>');
        } else {
            $form = '<p>Folgende Konditionen gelten bei Registrierung der Domain im nächsten Schritt:</p>
                <table>
                <tr><td>Domainname:</td><td><strong>' . filter_output_html($request) . '</strong></td></tr>
                <tr><td>Jahresgebühr:</td><td style="text-align: right;">' . $data['gebuehr'] . ' €' . footnote('Bruttobetrag inkl. 19% deutsche USt. Nettopreise für innergemeinschaftlichen Handel können vom Support eingetragen werden.') . '</td></tr>';
            if ($data['setup']) {
                $form .= '<tr><td>Setup-Gebühr (einmalig):</td><td style="text-align: right;">' . $data['setup'] . ' €' . footnote('Bruttobetrag inkl. 19% deutsche USt. Nettopreise für innergemeinschaftlichen Handel können vom Support eingetragen werden.') . '</td></tr>';
            }
            $form .= '</table>';

            $form .= '<p><input type="hidden" name="domain" value="' . filter_output_html($request) . '">
                <input type="submit" name="submit" value="Ich möchte diese Domain registrieren"></p>';
            output(html_form('domains_register', 'domainreg', '', $form));
            output('<p>' . internal_link('domains', 'Zurück') . '</p>');
        }
    } elseif ($avail['status'] == 'registered' || $avail['status'] == 'alreadyRegistered') {
        output('<p class="domain-unavailable">Die Domain ' . filter_output_html($request) . ' ist bereits vergeben.</p>');

        output('<h3>Domain zu ' . config('company_name') . ' umziehen</h3>');
        if ($avail['status'] == 'registered' && $avail['transferMethod'] != 'authInfo') {
            output('<p>Diese Domainendung kann nicht automatisiert übertragen werden. Bitte wenden Sie sich an den Support.</p>');
        } else {
            $data = get_domain_offer($avail['domainSuffix']);

            if ($data === false) {
                output('<p>Diese Endung ist für die automatische Registrierung nicht freigeschaltet. Bitte fragen Sie bei unserem Support nach den Konditionen und Bedingungen für diese Domain-Endung!</p>');
            } else {
                $form = '<p>Folgende Konditionen gelten beim Transfer der Domain im nächsten Schritt:</p>
                    <table>
                    <tr><td>Domainname:</td><td><strong>' . filter_output_html($avail['domainNameUnicode']) . '</strong></td></tr>
                    <tr><td>Jahresgebühr:</td><td style="text-align: right;">' . $data['gebuehr'] . ' €' . footnote('Bruttobetrag inkl. 19% deutsche USt. Nettopreise für innergemeinschaftlichen Handel können vom Support eingetragen werden.') . '</td></tr>';
                if ($data['setup']) {
                    $form .= '<tr><td>Setup-Gebühr (einmalig):</td><td style="text-align: right;">' . $data['setup'] . ' €' . footnote('Bruttobetrag inkl. 19% deutsche USt. Nettopreise für innergemeinschaftlichen Handel können vom Support eingetragen werden.') . '</td></tr>';
                }
                $form .= '</table>';


                $form .= '<p><input type="hidden" name="domain" value="' . filter_output_html($avail['domainNameUnicode']) . '">
                    <input type="submit" name="submit" value="Ich möchte diese Domain zu ' . config('company_name') . ' umziehen"></p>';

                output(html_form('domains_transferin', 'domainreg', '', $form));
            }
        }
        output('<h3>Diese Domain als externe Domain nutzen</h3>');
        output('<p>Sie können diese Domain für Konfigurationen bei uns nutzen ohne einen Transfer vorzunehmen.</p>
                <p><strong>Beachten Sie:</strong> Um diese Domain nutzen zu können, benötigen Sie bei Ihrem bisherigen Domainregistrar die Möglichkeit, DNS-Records anzulegen oder die zuständigen DNS-Server zu ändern. Sie können dann entweder unsere DNS-Server nutzen oder einzelne DNS-Records auf unsere Server einrichten.</p>');

        output('<p>Mit Betätigen des unten stehenden Knopfes bestätigen Sie, dass Sie entweder der Domaininhaber sind oder mit expliziter Zustimmung des Domaininhabers handeln.</p>');
        $form = '<p>
            <span class="buttonset" id="buttonset-external">
            <input type="radio" name="dns" id="option-dns-enable" value="enable" />
            <label for="option-dns-enable">Lokalen DNS-Server aktivieren</label>
            <input type="radio" name="dns" id="option-dns-disable" value="disable" checked="checked" />
            <label for="option-dns-disable">Weiterhin externen DNS verwenden</label>
            </span>
            </p><p>
            <span class="buttonset" id="buttonset-email">
            <input type="radio" name="email" id="option-email-enable" value="enable" checked="checked" />
            <label for="option-email-enable">E-Mail-Nutzung aktivieren</label>
            <input type="radio" name="email" id="option-email-disable" value="disable" />
            <label for="option-email-disable">Nicht für E-Mail nutzen</label>
            </span></p>';

        $form .= '<p><input type="hidden" name="domain" value="' . filter_output_html($request) . '">
            <input type="submit" name="submit" value="Diese Domain bei ' . config('company_name') . ' verwenden"></p>';

        output(html_form('domains_external', 'useexternal', '', $form));
    } else {
        output('<p class="domain-unavailable">Die Domain ' . filter_output_html($request) . ' kann nicht registriert werden.</p>');

        switch ($avail['status']) {
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
