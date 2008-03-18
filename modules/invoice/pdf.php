<?php
require_once('session/start.php');
require_once('invoice.php');
require_role(ROLE_CUSTOMER);

$pdfdata = get_pdf($_GET['id']);
if (! $pdfdata)
{
	system_failure('Die PDF-Version dieser Rechnung konnte nicht aufgerufen werden. PDF-Versionen für sehr alte Rechnungen sind nicht mehr verfügbar.');
}
else
{
	header('Content-type: application/pdf');
	header('Content-disposition: attachment; filename=rechnung.pdf');
	echo $pdfdata;
	die();
}

?>
