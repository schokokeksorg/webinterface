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

require_role(ROLE_CUSTOMER);

$dom = NULL;
if (!isset($_REQUEST['id'])) {
    system_failure("Keine Domain angegeben!");
}
$dom = new Domain( (int) $_REQUEST['id']);
$dom->ensure_customerdomain();
if ($dom->mailserver_lock === 0) {
    system_failure("Diese Domain ist momentan nicht gesperrt. Entsperrung nicht notwendig.");
}
if ($dom->provider == 'terions') {
    system_failure("Diese Domain ist bereits über uns registriert und sollte schon freigeschaltet sein. Wenden Sie sich im Zweifel bitte an den Support.");
}
if (has_own_ns($dom->domainname, $dom->tld)) {
    unset_mailserver_lock($dom);
    success_msg("Die Domain {$dom->fqdn} wurde erfolgreich bestätigt und kann nun in vollem Umfang verwendet werden.");
    redirect("domains");
}
if (!$dom->secret) {
    create_domain_secret($dom);
}

$TXT = get_txt_record('_schokokeks', $dom->domainname, $dom->tld);
if ($TXT == $dom->secret) {
    unset_mailserver_lock($dom);
    success_msg("Die Domain {$dom->fqdn} wurde erfolgreich bestätigt und kann nun in vollem Umfang verwendet werden.");
    redirect("domains");
}


title("Externe Domain {$dom->fqdn}");
$section = 'domains_domains';
output('<p>Die Domain '.$dom->fqdn.' ist momentan extern registriert und verwendet auch externe DNS-Server. Um Missbrauch zu verhindern, kann diese Domain daher bislang nicht als E-Mail-Domain benutzt werden. Um die Domain als E-Mail-Domain zu nutzen, können Sie diese zu uns umziehen, beim Registrar die DNS-Server von '.config('company_name').' einsetzen oder die Inhaberschaft bestätigen durch das Setzen eines passenden DNS-Records.</p>');
output('<h4>Domain-Transfer ausführen</h4>
<p></p>'); //FIXME
if ($dom->dns == 1 || have_module('dns')) {
    output('<h4>DNS-Server von '.config('company_name').' nutzen</h4>');
    if ($dom->dns == 0) {
        output('<p>Bisher ist der lokale DNS-Server ausgeschaltet. Besuchen Sie die DNS-Einstellungen um dies zu ändern.</p>');
        output('<p>'.internal_link('../dns/dns', 'DNS-Einstellungen aufrufen').'</p>');
    } else {
        $own_ns = own_ns();
        asort($own_ns);
        output('<p>Tragen Sie bitte folgende DNS-Server als zuständig für diese Domain ein:</p>
                <ul>');
        foreach ($own_ns as $ns) {
            output('<li>'.$ns.'</li>');
        }
        output('</ul>');
    }
}
output('<h4>Inhaberschaft bestätigen</h4>');
output('<p>Um eine extern registrierte Domain hier in vollem Umfang zu nutzen, müssen Sie die Inhaberschaft bestätigen. Erst nach diesem Schritt können Sie diese Domain bei uns als Mail-Domain nutzen.</p>');
output('<p>Die Zeichenkette zur Bestätigung lautet <strong>'.$dom->secret.'</strong>.</p>');
output('<p>Richten Sie bitte auf dem zuständigen DNS-Server einen DNS-Record vom Typ TXT unter dem Hostname <strong>_schokokeks.'.$dom->fqdn.'</strong> ein und hinterlegen Sie dort diese Zeichenkette als Inhalt:</p>
<p><code>_schokokeks.'.$dom->fqdn.'.    IN TXT "'.$dom->secret.'"</code></p>
<p>Beachten Sie, dass Aktualisierungen am DNS-Server i.d.R. mit einigen Minuten verzögerung abgerufen werden können.</p>');
#output('<p>Sie können diese entweder als DNS-Record vom Typ TXT unter dem Hostname <strong>_schokokeks.'.$dom->fqdn.'</strong> einrichten oder auf dem zuständigen Webserver eine Datei hinterlegen mit dem Code als Inhalt und der Abruf-URL <strong>http://'.$dom->fqdn.'/'.$dom->secret.'.txt</strong></p>');
output('<p>'.internal_link('', other_icon('refresh.png').' Diese Seite neu laden um den DNS-Record zu prüfen', "id={$dom->id}&ts=".time()).'</p>');
output('<p>Nach erfolgreicher Überprüfung kann der DNS-Eintrag wieder entfernt werden.</p>');


