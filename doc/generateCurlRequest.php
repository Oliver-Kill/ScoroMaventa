<?php

require_once '../functions.php';

$command = "curl %url% -H 'Content-type: application/json; charset=utf-8' -d '%data%'";
$command = preg_replace("/%url%/", dirname(getBaseUrl()) . "/sendInvoice.php", $command);
$command = preg_replace("/%data%/", file_get_contents("invoiceCreatedRequest.json"), $command);
echo $command;