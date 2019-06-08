<?php

require_once "../utility/db.php";
require_once "../utility/utility.php";
if (session_status() == PHP_SESSION_NONE) {
	sec_session_start();
}

if (login_check($conn) === true) {
	echo "<a href='#' id='logoutLink' >Logout</a>
		<a href='#' id='buyLink'>Buy</a>
		<a href='#' id='updateLink' >Update</a>";
} else {
	echo "<a href='#' id='updateLink' >Homepage</a>
			<a href='#' id='registerLink' >Register</a>
		<a href='#' id='loginLink' >Login</a>";
}

?>
