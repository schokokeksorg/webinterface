<?php
/*
This file belongs to the Webinterface of schokokeks.org Hosting

Written by schokokeks.org Hosting, namely
  Bernd Wurst <bernd@schokokeks.org>
  Hanno Böck <hanno@schokokeks.org>

This code is published under a 0BSD license.

Nevertheless, in case you use a significant part of this code, we ask (but not require, see the license) that you keep the authors' names in place and return your changes to the public. We would be especially happy if you tell us what you're going to do with this code.
*/

function save_passkey($data, $handle = null)
{
    require_role(ROLE_SYSTEMUSER);
    $args = [
        ":credentialId" => $data->credentialId,
        ":credentialPublicKey" => $data->credentialPublicKey,
        ":rpId" => $data->rpId,
        ":handle" => $handle,
        ":uid" => $_SESSION['userinfo']['uid'],
        ];
    db_query("INSERT INTO system.systemuser_passkey (uid, handle, rpId, credentialId, credentialPublicKey) VALUES " .
            "(:uid, :handle, :rpId, :credentialId, :credentialPublicKey)", $args);
}

function get_passkey($id)
{
    $ret = db_query("SELECT uid, handle, credentialPublicKey FROM system.systemuser_passkey WHERE credentialId=:id", [":id" => $id]);
    if ($data = $ret->fetch()) {
        return $data;
    }
    return null;
}


function list_passkeys()
{
    $result = db_query("SELECT id, handle, setuptime, rpId FROM system.systemuser_passkey WHERE uid=:uid", [":uid" => $_SESSION['userinfo']['uid']]);
    $ret = [];
    while ($item = $result->fetch()) {
        $ret[] = $item;
    }
    return $ret;
}

function delete_systemuser_passkey($id)
{
    $args = [
        ":id" => $id,
        ":uid" => $_SESSION['userinfo']['uid'],
        ];
    db_query("DELETE FROM system.systemuser_passkey WHERE uid=:uid AND id=:id", $args);
}
