<?php

require_once 'db.php';
require_once 'utility.php';

/*Check for HTTPS*/
if ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'off') || $_SERVER['SERVER_PORT'] != 443) {
	echo json_encode(ErrorObject::HTTPS_ENFORCE);
	return;
}

/*Start if not present a secure session*/
if (session_status() == PHP_SESSION_NONE) {
	sec_session_start();
}

/*Check if correct data*/
if (!isset($_POST["action"]) || !in_array($_POST['action'], array('login', 'logout', 'register')) ||
	($_POST['action'] != 'logout' && !isset($_POST['email'], $_POST['p']))) {
	echo json_encode(ErrorObject::MISSING_DATA);
	return;
}

/*Check if it's a logout action*/
if ($_POST["action"] == "logout") {
	echo json_encode(logout());
	return;
}

/*Sanitizing parameters*/
$email = $_POST['email'];
$pass = $_POST['p'];
$_POST['email'] = filter_var($email, FILTER_SANITIZE_STRING);
$_POST['p'] = filter_var($pass, FILTER_SANITIZE_STRING);

/*Check if they had some code*/
if ($email != $_POST['email'] || $pass != $_POST['p']) {
	echo json_encode(ErrorObject::CODE_INJECTION);
	return;
}

/*Check if email compliant*/
if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
	echo json_encode(ErrorObject::EMAIL_NOT_COMPLIANT);
	return;
}

/*Check if password compliant*/
if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z0-9]).*$/', $_POST['p'])) {
	echo json_encode(ErrorObject::PASSWORD_NOT_COMPLIANT);
	return;
}

/*Check if it's a login request*/
if ($_POST["action"] == "login") {
	echo json_encode(login($_POST['email'], $_POST['p'], $conn));
	return;
}

/*Check if it's a register request*/
if ($_POST["action"] == "register") {
	echo json_encode(register($_POST['email'], $_POST['p'], $conn));
	return;
}

?>
