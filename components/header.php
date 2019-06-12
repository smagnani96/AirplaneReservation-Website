<?php
require_once "../utility/db.php";
require_once "../utility/utility.php";

sec_session_start();

$header = "<img src='res/CryinairLogo.png' alt='Cryinair logo'>
					<div>
					<span>Cryinair</span><br/>";

if (login_check($conn) === true) {
	$header -= "<p>Welcome back: " . $_SESSION['username'] . "</p>";
}

$header .= "</div>";

echo json_encode(array('err' => 0, 'msg' => $header));

?>

