<?php

require_once 'db.php';
require_once 'utility.php';

sec_session_start();

/*Check for HTTPS*/
if ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'off') || $_SERVER['SERVER_PORT'] != 443) {
	echo json_encode(ErrorObject::HTTPS_ENFORCE);
	return;
}

/*Check if correct data*/
if (!isset($_POST["action"]) || !in_array($_POST['action'], array('login', 'logout', 'register')) ||
	($_POST['action'] != 'logout' && !isset($_POST['email'], $_POST['p'])) || ($_POST['action'] == "login" && !isset($_POST['remember']))) {
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
$_POST['email'] = filter_var($email, FILTER_VALIDATE_EMAIL);
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
	$ret = login($_POST['email'], $_POST['p'], $conn);
	if ($_POST['remember'] == 1 && $ret['msg'] == 0) {
		setcookie("email", $email, time() + (86400 * 30), "/", "", true, true);
		setcookie("password", $pass, time() + (86400 * 30), "/", "", true, true);
	}
	echo json_encode($ret);
	return;
}

/*Check if it's a register request*/
if ($_POST["action"] == "register") {
	$ret = register($_POST['email'], $_POST['p'], $conn);
	if ($ret['err'] == 0) {
		login($_POST['email'], $_POST['p'], $conn);
	}
	echo json_encode($ret);
	return;
}


