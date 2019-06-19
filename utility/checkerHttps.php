<?php

require_once "checkerAccess.php";

if ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'off') || $_SERVER['SERVER_PORT'] != 443) {
	echo json_encode(array('err' => -1, 'msg' => "To perform that operation you must access through HTTPS."));
	die();
}
