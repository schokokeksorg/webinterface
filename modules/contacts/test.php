<?php

require_once('contactapi.php');
require_once('inc/base.php');

require_once('contacts.php');


title('Kontakt hochladen');

if (isset($_SESSION['contacts_upload'])) {
    $c = get_contact($_SESSION['contacts_upload']);
    unset($_SESSION['contacts_upload']);
    unset($_SESSION['contacts_choose_key']);
    unset($_SESSION['contacts_choose_header']);
    unset($_SESSION['contacts_choose_redirect']);
    upload_contact($c);
    output('<p>Kontakt gewählt:</p>'.display_contact($c));
} else {
    $_SESSION['contacts_choose_header'] = 'Wählen Sie einen Kontakt zum Hochladen.';
    $_SESSION['contacts_choose_key'] = 'contacts_upload';
    $_SESSION['contacts_choose_redirect'] = 'test';
    redirect('choose');
}
