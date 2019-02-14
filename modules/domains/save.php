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

require_once("class/domain.php");
require_once("domains.php");
require_once("domainapi.php");
require_role(ROLE_CUSTOMER);

$dom = new Domain((int) $_REQUEST['domain']);
$dom->ensure_customerdomain();

$domain_in_use = mailman_subdomains($dom->id) || mail_in_use($dom->id) || web_in_use($dom->id) || $dom->jabber == 1;
if ($_REQUEST['action'] == 'delete') {
    if ($domain_in_use || !($dom->status == 'prereg' || $dom->status == 'pretransfer' || $dom->status == 'transferfailed' || $dom->status == 'external')) {
        system_failure("Diese Domain ist noch in Benutzung. Bitte Postfächer und Websites löschen sowie Eintragungen in Mailinglisten oder Jabber-Server löschen lassen.");
    }
    $sure = user_is_sure();
    if ($sure === null) {
        are_you_sure("action=delete&domain={$dom->id}", "Möchten Sie die Domain »{$dom->fqdn}« wirklich löschen?");
    } elseif ($sure === true) {
        delete_domain($dom->id);
        redirect('domains');
    } elseif ($sure === false) {
        redirect('detail?id='.$dom->id);
    }
} elseif ($_REQUEST['action'] == 'cancel') {
    $info = api_download_domain($dom->id);
    $sure = user_is_sure();
    if ($sure === null) {
        are_you_sure("action=cancel&domain={$dom->id}", "Möchten Sie die Domain »{$dom->fqdn}« wirklich kündigen?<br>Wichtig: Bei einem Umzug ist keine separate Kündigung nötig. Bitte kündigen Sie nur, wenn Sie die Domain löschen und freigeben möchten.<br>Das Kündigungsdatum wäre dann {$info['currentContractPeriodEnd']}");
    } elseif ($sure === true) {
        api_cancel_domain($dom->fqdn);
        redirect('detail?id='.$dom->id);
    } elseif ($sure === false) {
        redirect('detail?id='.$dom->id);
    }
} elseif ($_REQUEST['action'] == 'transfer') {
    check_form_token('domains_transfer');
    api_unlock_domain($dom->fqdn);
    redirect('detail?id='.$dom->id);
}
