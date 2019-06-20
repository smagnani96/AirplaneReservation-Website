<?php

require_once "../utility/db.php";
require_once "../utility/utility.php";
require_once "../utility/checkerAccess.php";

sec_session_start();

$header = "<span>Cryinair</span><br/>";

if (login_check($conn) === true) {
	$header .= "<p>Welcome back: " . $_SESSION['username'] . "</p>";
}

echo json_encode(array('err' => 0, 'msg' => $header));

