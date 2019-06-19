<?php

require_once "config.php";
require_once "checkerAccess.php";

$conn = new mysqli(SERVERNAME, USERNAME, PASSWORD, DATABASE) or die;
if ($conn->connect_errno) {
	die("Failed to connect to MySQL: (" . $conn->connect_errno . ") " . $conn->connect_error);
}
