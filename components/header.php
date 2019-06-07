<img src="res/CryinairLogo.png" alt="Cryinair logo">
<span>Cryinair</span>

<?php
	require_once("../utility/db.php"); 
	require_once("../utility/utility.php");
	if (session_status() == PHP_SESSION_NONE) 
    	sec_session_start();

	if(login_check($conn) == true)
		echo "<br/><p>Welcome back: " . $_SESSION["username"] . "</p>";
?>