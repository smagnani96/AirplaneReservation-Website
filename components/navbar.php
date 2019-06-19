<?php

require_once "../utility/db.php";
require_once "../utility/utility.php";
require_once "../utility/checkerAccess.php";

sec_session_start();

if (login_check($conn) === true) {
	$navBar = "<a href='#' id='logoutLink' >Logout</a>
		<a href='#' id='updateLink' >Update</a>
		<a href='#' id='buyLink'>Buy</a>";
} else {
	$navBar = "<a href='#' id='updateLink' >Homepage</a>
			<a href='#' id='registerLink' >Register</a>
		<a href='#' id='loginLink' >Login</a>";
}

echo json_encode(array('err' => 0, 'msg' => $navBar));
