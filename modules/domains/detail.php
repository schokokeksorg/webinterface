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

require_once('inc/debug.php');
require_once('inc/icons.php');

require_once('class/domain.php');
require_once('domains.php');

require_role(array(ROLE_CUSTOMER, ROLE_SYSTEMUSER));

$dom = null;
if (isset($_REQUEST['id'])) {
    $dom = new Domain((int) $_REQUEST['id']);
    $_SESSION['domains_detail_domainname'] = $dom->fqdn;
} elseif (isset($_SESSION['domains_detail_domainname'])) {
    $dom = new Domain($_SESSION['domains_detail_domainname']);
} else {
    system_failure("Keine Domain angegeben");
}
if (!$dom) {
    system_failure("Keine Domain gewählt!");
}
if (have_role(ROLE_CUSTOMER)) {
    $dom->ensure_customerdomain();
} else {
    $dom->ensure_userdomain();
}

title("Domain {$dom->fqdn}");
$section = 'domains_domains';

// Block zuständiger Useraccount

$is_current_user = true;
$useraccounts = list_useraccounts();
if (have_role(ROLE_CUSTOMER) && count($useraccounts) > 1) {
    if ($dom->useraccount != $_SESSION['userinfo']['uid']) {
        $is_current_user = false;
    }
    // Mehrere User vorhanden
    $options = array();
    foreach ($useraccounts as $u) {
        $options[$u['uid']] = $u['username'];
    }
    if (!array_key_exists($dom->useraccount, $options)) {
        $options[$dom->useraccount] = $dom->useraccount;
    }
    output('<h4>Zuständiges Benutzerkonto</h4>');
    $form = '<p>Diese Domain nutzen im Benutzerkonto '.html_select('domainuser', $options, $dom->useraccount).' <input type="submit" name="submit" value="Änderung speichern"></p>';
    output(html_form('update-user', 'update', 'action=chguser&id='.$dom->id, $form));
} elseif (!have_role(ROLE_SYSTEMUSER) || $dom->useraccount != $_SESSION['userinfo']['uid']) {
    // Kunde hat keine mehreren User, Domain ist trotzdem in einem anderen Useraccount
    $is_current_user = false;
    output('<h4>Zuständiges Benutzerkonto</h4>');
    output('<p>Diese Domain wird im Benutzerkonto mit der User-ID #'.$dom->useraccount.' verwendet.</p>');
}


// Block Nutzung

if ($is_current_user) {
    output("<h4>Aktuelle Nutzung dieser Domain</h4>");
    output('<div class="tile-container">');
    $everused = false;
    if (have_module('dns') && $dom->dns == 1) {
        $used = dns_in_use($dom->id);
        output("<div class=\"tile usage ".($used ? "used" : "unused")."\"><p><strong>".internal_link('../dns/dns_domain', "DNS-Server", 'dom='.$dom->id)."</strong></p><p>".($used ? "Manuelle DNS-Records vorhanden." : "DNS-Records möglich")."</p></div>");
        $everused = true;
    }
    if (have_module('email') && ($dom->mail != 'none')) {
        if ($dom->provider != 'terions') {
            $mxresult = dns_get_record($dom->fqdn, DNS_MX);
            $found = false;
            foreach ($mxresult as $mx) {
                if (substr_compare($mx['target'], config('masterdomain'), -strlen(config('masterdomain'))) === 0) {
                    $found = true;
                }
            }
            if (! $found) {
                DEBUG('MX für '.$dom->fqdn.':');
                DEBUG($mxresult);
                warning('Bei dieser Domain ist der Mail-Empfang aktiviert, jedoch verweist das DNS-System scheinbar nicht auf unsere Anlagen. Wenn Sie keine E-Mails empfangen möchten, schalten Sie die Mail-Verarbeitung für diese Domain aus.');
            }
        }
        $used = mail_in_use($dom->id);
        $vmail = count_vmail($dom->id);
        if ($used) {
            if ($vmail > 0) {
                output("<div class=\"tile usage used\"><p><strong>".internal_link('../email/vmail', "E-Mail", 'filter='.$dom->fqdn)."</strong></p><p>E-Mail-Postfächer unter dieser Domain: <strong>{$vmail}</strong></p></div>");
            } else {
                output("<div class=\"tile usage unused\"><p><strong>".internal_link('../email/imap', "E-Mail")."</strong></p><p>Manuelle Mail-Konfiguration ist aktiv</p></div>");
            }
        } else {
            output("<div class=\"tile usage unused\"><p><strong>".internal_link('../email/vmail', "E-Mail", 'filter='.$dom->fqdn)."</strong></p><p>Bisher keine E-Mail-Postfächer unter dieser Domain.</p></div>");
        }
        $everused = true;
    }
    if (have_module('mailman') && mailman_subdomains($dom->id)) {
        $mailmanhosts = mailman_subdomains($dom->id);
        $hostname = $dom->fqdn;
        if (count($mailmanhosts) == 1) {
            $hostname = $mailmanhosts[0]['hostname'].'.'.$dom->fqdn;
        }
        output("<div class=\"tile usage used\"><p><strong>".internal_link('../mailman/lists', "Mailinglisten", 'filter='.$hostname)."</strong></p><p>Diese Domain wird für Mailinglisten verwendet</p></div>");
        $used = true;
        $everused = true;
    }
    if (have_module('vhosts')) {
        $used = web_in_use($dom->id);
        output("<div class=\"tile usage ".($used ? "used" : "unused")."\"><p><strong>".internal_link('../vhosts/vhosts', "Websites", 'filter='.$dom->fqdn)."</strong></p><p>".($used ? "Es gibt Website-Einstellungen für diese Domain" : "Bisher keine Website eingerichtet")."</p></div>");
        $everused = true;
    }
    if (have_module('jabber')) {
        if ($dom->jabber == 1) {
            output("<div class=\"tile usage used\"><p><strong>".internal_link('../jabber/accounts', "Jabber/XMPP")."</strong></p><p>Diese Domain wird für Jabber verwendet</p></div>");
        } else {
            output("<div class=\"tile usage unused\"><p><strong>".internal_link('../jabber/new_domain', "Jabber/XMPP")."</strong></p><p>Diese Domain wird bisher nicht für Jabber verwendet</p></div>");
        }
        $everused = true;
    }
    output('</div>');
    if (! $everused) {
        output('<p><em>Keine Nutzung dieser Domain (die hier angezeigt wird)</em></p>');
    }
}

// Block Domain-Inhaber

if (have_role(ROLE_CUSTOMER) && config('http.net-apikey') && $dom->provider == 'terions' && ($dom->cancel_date === null || $dom->cancel_date > date('Y-m-d'))) {
    use_module('contacts');
    require_once('contacts.php');
    require_once('domainapi.php');

    output('<h4>Inhaberwechsel der Domain</h4>');
    output('<p>Legen Sie hier einen neuen Inhaber für diese Domain fest.</p>');

    if (isset($_REQUEST['id'])) {
        api_download_domain($_REQUEST['id']);
        $_SESSION['domains_detail_domainname'] = $dom->fqdn;
        $_SESSION['domains_detail_owner'] = $dom->owner;
        $_SESSION['domains_detail_admin_c'] = $dom->admin_c;
    }
    if (!update_possible($dom->id)) {
        warning("Diese Domain verwendet eine unübliche Endung. Daher kann der Inhaber nicht auf diesem Weg verändert werden. Bitte kontaktieren Sie den Support.");
    } else {
        if ($_SESSION['domains_detail_admin_c'] == $dom->admin_c &&
                $_SESSION['domains_detail_owner'] != $dom->owner &&
                (!isset($_SESSION['domains_detail_detach']) || $_SESSION['domains_detail_detach'] == 0)) {
            // Wenn der Owner geändert wurde, der Admin aber nicht und das detach-Flag
            // nicht gesetzt ist, dann wird der Admin gleich dem Owner gesetzt
            $_SESSION['domains_detail_admin_c'] = $_SESSION['domains_detail_owner'];
        }

        if (isset($_GET['admin_c']) && $_GET['admin_c'] == 'none') {
            $_SESSION['domains_detail_admin_c'] = $_SESSION['domains_detail_owner'];
            unset($_SESSION['domains_detail_detach']);
        }

        $owner = get_contact($_SESSION['domains_detail_owner']);
        $admin_c = get_contact($_SESSION['domains_detail_admin_c']);
        $function = 'Inhaber';
        if ($owner['id'] == $admin_c['id']) {
            $function .= ' und Verwalter';
        }
        $cssclass = '';
        if ($owner['id'] != $dom->owner) {
            $cssclass = 'modified';
        }
        output('<p><strong>'.$function.':</strong></p>'.display_contact($owner, '', $cssclass));
        addnew('choose', 'Neuen Inhaber wählen', "type=owner");
        if ($owner['id'] != $admin_c['id']) {
            $cssclass = '';
            if ($admin_c['id'] != $dom->admin_c) {
                $cssclass = 'modified';
            }
            output('<p><strong>Verwalter:</strong></p>'.display_contact($admin_c, '', $cssclass));
            addnew('choose', 'Neuen Verwalter wählen', "type=admin_c");
            output('<p class="delete">'.internal_link('', 'Keinen separaten Verwalter festlegen', 'admin_c=none').'</p>');
        } else {
            addnew('choose', 'Einen separaten Verwalter wählen', "type=admin_c&detach=1");
        }


        if ($owner['id'] != $dom->owner || $admin_c['id'] != $dom->admin_c) {
            if (isset($_GET['error']) && $_GET['error'] == '1') {
                input_error('Sie müssen der Übertragung explizit zustimmen!');
            }
            $form = '<p>Es sind Änderungen vorgenommen worden, die noch nicht gespeichert wurden</p>';
            $form .= '<p><input type="checkbox" name="accept" value="1" id="accept"><label for="accept"> Ich bestätige, dass ich die nachfolgenden Hinweise zur Kenntnis genommen habe.</p>
                <p>Mit Speichern dieser Änderungen führen Sie möglicherweise einen Inhaberwechsel bei der Domain '.$dom->fqdn.' aus. Inhaberwechsel sind bei einigen Domainendungen (z.B. com/net/org) zustimmungspflichtig vom alten und vom neuen Inhaber. Die Registrierungsstelle kann nach eigenem Ermessen diese Zustimmung per separater E-Mail einfordern. Wird diese Zustimmung nicht oder verspätet erteilt, kann eine Domain gesperrt werden. Dieser Vorgang wird nicht von '.config('company_name').' kontrolliert.</p>
                <p>Sie sind ferner darüber informiert, dass die Adresse des Domaininhabers öffentlich abrufbar ist.</p>';
            $form .= '<p><input type="submit" name="sumbit" value="Änderungen speichern und Domaininhaber ändern"></p>';
            output(html_form('domains_update', 'update', "action=ownerchange&id=".$dom->id, $form));
        }
    }
}

// Block Externe Domain umziehen

if (have_role(ROLE_CUSTOMER) && config('http.net-apikey')) {
    if ($dom->status == 'prereg') {
        output('<h4>Domain-Registrierung abschließen</h4>
                <p>'.internal_link('domainreg', 'Domain registrieren', "domain={$dom->fqdn}").'</p>');
    } elseif ($dom->status == 'pretransfer') {
        output('<h4>Domain zu '.config('company_name').' umziehen</h4>
                <p>'.internal_link('domainreg', 'Umzugsautrag (ggf. nochmals) erteilen', "domain={$dom->fqdn}").'</p>');
    } elseif ($dom->provider != 'terions') {
        output('<h4>Domain zu '.config('company_name').' umziehen</h4>
                <p>'.internal_link('domainreg', 'Domain-Transfer starten', "domain={$dom->fqdn}").'</p>');
    }
}

// Block Domain löschen/kündigen

$domain_in_use = mailman_subdomains($dom->id) || mail_in_use($dom->id) || web_in_use($dom->id) || $dom->jabber == 1;
if (!$domain_in_use && ($dom->status == 'prereg' || $dom->status == 'pretransfer' || $dom->status == 'transferfailed' || $dom->status == 'external')) {
    output('<h4>Domain wieder entfernen</h4>');
    output('<p class="delete">'.internal_link('save', 'Die Domain '.$dom->fqdn.' entfernen', 'action=delete&domain='.$dom->id).'</p>');
} elseif (have_role(ROLE_CUSTOMER) && config('http.net-apikey') && $dom->provider == 'terions' && (!$dom->cancel_date || ($dom->cancel_date > date('Y-m-d')))) {
    require_once('domainapi.php');
    output('<h4>Domain kündigen</h4>');
    $info = api_download_domain($dom->id);
    if ($info['authInfo']) {
        output('<p>Das Auth-Info für diese Domain lautet: <strong>'.$info['authInfo'].'</strong></p>');
        output('<p>Wenden Sie sich an den Support, wenn Sie den Domainumzug wieder sperren möchten.</p>');
    } else {
        output('<p>Hier können Sie die Domain zum Umzug freigeben.</p>');
        $form = '<p><input type="hidden" name="domain" value="'.$dom->id.'"><input type="submit" name="submit" value="Die Domain '.$dom->fqdn.' zum Umzug freigeben"></p>';
        output(html_form('domains_transfer', 'save', 'action=transfer', $form));
    }
    output('<p>Die aktuelle Laufzeit der Domain dauert noch bis '.$info['currentContractPeriodEnd'].'</p>');
    if ($info['deletionDate']) {
        output('<p>Es liegt aktuell eine Kündigung vor auf <strong>'.$info['deletionDate'].'</strong></p><p>Um die Kündigung aufzuheben, wenden Sie sich bitte an den Support.</p>');
    } else {
        output('<p>Die Laufzeit wird automatisch um ein weiteres Jahr verlängert, sofern Sie keine Kündigung auslösen oder die Domain zu einem anderen Anbieter umziehen.</p>');
        output('<p class="delete">'.internal_link('save', 'Die Domain '.$dom->fqdn.' kündigen', 'action=cancel&domain='.$dom->id).'</p>');
    }
}


// Block Domain bestätigen

if ($dom->mailserver_lock == 1 && $dom->status != 'prereg') {
    if (has_own_ns($dom->domainname, $dom->tld)) {
        unset_mailserver_lock($dom);
        success_msg("Die Domain {$dom->fqdn} wurde erfolgreich bestätigt und kann nun in vollem Umfang verwendet werden.");
        redirect("");
    }
    output('<h3>Mailserver-Sperre aktiv</h3>
            <p>Bisher ist für diese Domain die Nutzung als Mail-Domain eingeschränkt, da wir noch keine Gewissheit haben, ob Sie der rechtmäßige Nutzer der Domain sind. Eine Domain, die für E-Mail-Aktivität genutzt werden soll, muss entweder die DNS-Server von '.config('company_name').' verwenden oder die Inhaberschaft muss durch einen passend gesetzten DNS-Record nachgewiesen werden. Nachfolgend werden die Möglichkeiten im Detail vorgestellt.</p>');
    if (!$dom->secret) {
        create_domain_secret($dom);
    }

    $TXT = get_txt_record('_schokokeks', $dom->domainname, $dom->tld);
    if ($TXT == $dom->secret) {
        unset_mailserver_lock($dom);
        success_msg("Die Domain {$dom->fqdn} wurde erfolgreich bestätigt und kann nun in vollem Umfang verwendet werden.");
        redirect("");
    }

    if ($dom->dns == 1 || have_module('dns')) {
        output('<h4>DNS-Server von '.config('company_name').' nutzen</h4>');
        output('<p>Wenn Sie die lokalen DNS-Server als zuständig einrichten, wird die Domain automatisch bestätigt.</p>');
        if ($dom->dns == 0) {
            output('<p>Bisher ist der lokale DNS-Server ausgeschaltet. Besuchen Sie die DNS-Einstellungen um dies zu ändern.</p>');
            output('<p>'.internal_link('../dns/dns', 'DNS-Einstellungen aufrufen').'</p>');
        } else {
            $own_ns = own_ns();
            asort($own_ns);
            output('<p>Wenn Sie die DNS-Server von '.config('company_name').' nutzen möchten, dann richten Sie bei Ihrem Domain-Registrar bitte folgende DNS-Server als zuständig für diese Domain ein:</p>
                    <ul>');
            foreach ($own_ns as $ns) {
                output('<li>'.$ns.'</li>');
            }
            output('</ul>');
            output('<p>Nachdem die Änderungen bei der Registrierungsstelle übernommen wurden (das kann mehrere Stunden dauern), reicht ein erneuter Aufruf dieser Seite um die Sperrung aufzuheben.</p>');
        }
    }
    output('<h4>Inhaberschaft bestätigen</h4>');
    output('<p>Um eine extern registrierte Domain in vollem Umfang zu nutzen, ohne die lokalen DNS-Server als zuständig einzurichten, müssen Sie die Inhaberschaft bestätigen. Erst nach diesem Schritt können Sie diese Domain bei '.config('company_name').' als Mail-Domain nutzen.</p>');

    output('<p>Die Zeichenkette zur Bestätigung lautet <strong>'.$dom->secret.'</strong>.</p>');
    output('<p>Richten Sie bitte auf dem zuständigen DNS-Server einen DNS-Record vom Typ TXT unter dem Hostname <strong>_schokokeks.'.$dom->fqdn.'</strong> ein und hinterlegen Sie dort diese Zeichenkette als Inhalt:</p>
            <p><code>_schokokeks.'.$dom->fqdn.'.    IN TXT "'.$dom->secret.'"</code></p>
            <p>Beachten Sie, dass Aktualisierungen am DNS-Server i.d.R. mit einigen Minuten verzögerung abgerufen werden können.</p>');
    #output('<p>Sie können diese entweder als DNS-Record vom Typ TXT unter dem Hostname <strong>_schokokeks.'.$dom->fqdn.'</strong> einrichten oder auf dem zuständigen Webserver eine Datei hinterlegen mit dem Code als Inhalt und der Abruf-URL <strong>http://'.$dom->fqdn.'/'.$dom->secret.'.txt</strong></p>');
    output('<p>'.internal_link('', other_icon('refresh.png').' Diese Seite neu laden um den DNS-Record zu prüfen', "id={$dom->id}&ts=".time()).'</p>');
    output('<p>Nach erfolgreicher Überprüfung kann der DNS-Eintrag wieder entfernt werden.</p>');
}


output('<p>'.internal_link('domains', 'Ohne Änderungen zurück').'</p>');
