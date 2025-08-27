<?php

/*
This file belongs to the Webinterface of schokokeks.org Hosting

Written by schokokeks.org Hosting, namely
  Bernd Wurst <bernd@schokokeks.org>
  Hanno Böck <hanno@schokokeks.org>

This code is published under a 0BSD license.

Nevertheless, in case you use a significant part of this code, we ask (but not require, see the license) that you keep the authors' names in place and return your changes to the public. We would be especially happy if you tell us what you're going to do with this code.
*/

function list_system_users()
{
    require_role(ROLE_SYSADMIN);

    $result = db_query("SELECT uid,username FROM system.v_useraccounts ORDER BY username");

    $ret = [];
    while ($item = $result->fetch(PDO::FETCH_OBJ)) {
        array_push($ret, $item);
    }
    return $ret;
}


function list_customers()
{
    require_role(ROLE_SYSADMIN);

    $result = db_query("SELECT id, IF(firma IS NULL, CONCAT_WS(' ', vorname, nachname), CONCAT(firma, ' (', CONCAT_WS(' ', vorname, nachname), ')')) AS name FROM kundendaten.kunden");

    $ret = [];
    while ($item = $result->fetch(PDO::FETCH_OBJ)) {
        array_push($ret, $item);
    }
    return $ret;
}

function customer_details($id)
{
    $id = (int) $id;
    $result = db_query("SELECT id, IF(firma IS NULL, CONCAT_WS(' ', vorname, nachname), CONCAT(firma, ' (', CONCAT_WS(' ', vorname, nachname), ')')) AS name FROM kundendaten.kunden WHERE id=?", [$id]);
    if ($result->rowCount() < 1) {
        return null;
    }
    $kunde = $result->fetch();
    return $kunde;
}


function find_customers($string)
{
    $args = [":string" => '%' . chop($string) . '%', ":number" => $string];
    $return = [];
    $result = db_query("SELECT k.id FROM kundendaten.kunden AS k LEFT JOIN system.useraccounts AS u ON (k.id=u.kunde) WHERE "
                     . "firma LIKE :string OR firma2 LIKE :string OR "
                     . "nachname LIKE :string OR vorname LIKE :string OR "
                     . "adresse LIKE :string OR adresse2 LIKE :string OR "
                     . "ort LIKE :string OR pgp_id LIKE :string OR "
                     . "notizen LIKE :string OR email_rechnung LIKE :string OR "
                     . "email LIKE :string OR email_extern LIKE :string OR u.name LIKE :string OR "
                     . "u.username LIKE :string OR k.id=:number OR u.uid=:number", $args);
    while ($entry = $result->fetch()) {
        $return[] = $entry['id'];
    }

    unset($args[':number']);
    $result = db_query("SELECT kunde FROM kundendaten.domains WHERE kunde IS NOT NULL AND (
                      domainname LIKE :string OR CONCAT_WS('.', domainname, tld) LIKE :string
                      )", $args);

    while ($entry = $result->fetch()) {
        $return[] = $entry['kunde'];
    }

    return $return;
}


function find_users_for_customer($id)
{
    $id = (int) $id;
    $return = [];
    $result = db_query("SELECT uid, username, name FROM system.useraccounts WHERE "
                     . "kunde=?", [$id]);
    while ($entry = $result->fetch()) {
        $return[] = $entry;
    }

    return $return;
}




function build_results($term)
{
    global $ret;
    $ret = [];

    $add = function ($val, $id, $value) {
        global $ret;
        if (isset($ret[$val]) && is_array($ret[$val])) {
            array_push($ret[$val], ["id" => $id, "value" => $value]);
        } else {
            $ret[$val] = [ ["id" => $id, "value" => $value] ];
        }
    };


    $result = array_unique(find_customers($term));
    sort($result);
    foreach ($result as $val) {
        $c = customer_details($val);
        if ($c['id'] == $term) {
            $add(10, "c{$c['id']}", "Kunde {$c['id']}: {$c['name']}");
        } else {
            $add(90, "c{$c['id']}", "Kunde {$c['id']}: {$c['name']}");
        }
        $users = find_users_for_customer($c['id']);
        foreach ($users as $u) {
            $realname = $c['name'];
            if ($u['name']) {
                $realname = $u['name'];
            }
            if ($u['uid'] == $term || $u['username'] == $term) {
                $add(15, "u{$u['uid']}", "{$u['username']} (UID {$u['uid']}, {$realname})");
            } elseif (strstr($u['username'], $term)) {
                $add(20, "u{$u['uid']}", "{$u['username']} (UID {$u['uid']}, {$realname})");
            } elseif (isset($u['name']) && stristr($u['name'], $term)) {
                $add(25, "u{$u['uid']}", "{$u['username']} (UID {$u['uid']}, {$realname})");
            } else {
                $add(85, "u{$u['uid']}", "{$u['username']} (UID {$u['uid']}, {$realname})");
            }
        }
    }

    ksort($ret);

    $allentries = [];
    foreach ($ret as $group) {
        usort($group, function ($a, $b) {
            return strnatcmp($a['value'], $b['value']);
        });
        foreach ($group as $entry) {
            $allentries[] = $entry;
        }
    }
    unset($ret);
    return $allentries;
}


function su($type, $id)
{
    $role = null;
    $admin_user = $_SESSION['userinfo']['username'];
    $_SESSION['admin_user'] = $admin_user;
    $role = find_role($id, '', true);
    if (!$role) {
        unset($_SESSION['admin_user']);
        return false;
    }
    setup_session($role, $id, 'su');
    if ($type == 'c') {
        if (!(ROLE_CUSTOMER & $_SESSION['role'])) {
            session_destroy();
            system_failure('Es wurde ein "su" zu einem Kundenaccount angefordert, das war aber kein Kundenaccount!');
        }
    } elseif ($type == 'u') {
        if (!(ROLE_SYSTEMUSER & $_SESSION['role'])) {
            session_destroy();
            system_failure('Es wurde ein "su" zu einem Benutzeraccount angefordert, das war aber kein Benutzeraccount!');
        }
    } elseif ($type) {
        // wenn type leer ist, dann ist es auch egal
        system_failure('unknown type');
    }

    redirect('../../go/index/index');
    die();
}
