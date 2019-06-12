<?php

require_once "../utility/db.php";
require_once "../utility/utility.php";

sec_session_start();

$navbar = "";
if (login_check($conn) === true) {
	$navbar .= "<a href='#' id='logoutLink' >Logout</a>
		<a href='#' id='buyLink'>Buy</a>
		<a href='#' id='updateLink' >Update</a>";
} else {
	$navbar .= "<a href='#' id='updateLink' >Homepage</a>
			<a href='#' id='registerLink' >Register</a>
		<a href='#' id='loginLink' >Login</a>";
}

echo json_encode(array('err' => 0, 'msg' => $navbar));
?>
