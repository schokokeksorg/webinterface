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

require_once('webapp-installer.php');


function validate_data($post)
{
    DEBUG('Validating Data:');
    DEBUG($post);
    $fields = ['adminuser', 'adminpassword', 'adminemail', 'wikiname'];
    foreach ($fields as $field) {
        if ((! isset($post[$field])) || $post[$field] == '') {
            system_failure('Nicht alle Werte angegeben ('.$field.')');
        }
    }

    $dbdata = create_webapp_mysqldb('mediawiki', $post['wikiname']);

    $adminuser =  ucfirst(chop($post['adminuser']));

    $salt = random_string(8);
    $salthash = ':B:' . $salt . ':' . md5($salt . '-' . md5($post['adminpassword']));

    $data = "adminuser={$adminuser}
adminpassword={$salthash}
adminemail={$post['adminemail']}
wikiname={$post['wikiname']}
dbname={$dbdata['dbname']}
dbuser={$dbdata['dbuser']}
dbpass={$dbdata['dbpass']}";
    DEBUG($data);
    return $data;
}
