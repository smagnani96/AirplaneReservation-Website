<?php

require_once "checkerAccess.php";

if ($_SERVER['HTTPS'] !== 'on') {
	echo json_encode(array('err' => -1, 'msg' => "To perform that operation you must access through HTTPS."));
	die();
}
