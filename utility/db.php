<?php

define('SERVERNAME', 'localhost');
define('USERNAME', 'root');
define('PASSWORD', '');
define('DATABASE', 'airplane_reservation');
$conn = new mysqli(SERVERNAME, USERNAME, PASSWORD, DATABASE) or die;
if ($conn->connect_errno) {
	die("Failed to connect to MySQL: (" . $conn->connect_errno . ") " . $conn->connect_error);
}

?>