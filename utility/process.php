<?php

require_once 'db.php';
require_once 'utility.php';

/*Start if not present a secure session*/
if (session_status() == PHP_SESSION_NONE) {
	sec_session_start();
}

/*Check if at least is specified the action*/
if (!isset($_POST["action"])) {
	echo json_encode(array('err' => -1, 'msg' => "Wrong data sent!"));
	return;
}

/*Check if it's a logout action*/
if ($_POST["action"] == "logout") {
	logout();
	echo json_encode(array('err' => 0, 'msg' => "Successfully logged out!"));
	return;
}

if (!isset($_POST['email'], $_POST['p'])) {
	echo json_encode(array('err' => -1, 'msg' => "Wrong data sent!"));
	return;
}

$filteredEmail = filter_var($_POST['email'], FILTER_SANITIZE_STRING);
$filteredPass = filter_var($_POST['p'], FILTER_SANITIZE_STRING);
if ($filteredEmail != $_POST['email'] || $filteredPass != $_POST['p']) {
	echo json_encode(array('err' => -1, 'msg' => "Did you try to fool me with some code?"));
	return;
}

/*Check if it's a login request*/
if ($_POST["action"] == "login") {
	if (login($_POST['email'], $_POST['p'], $conn)) {
		echo json_encode(array('err' => 0, 'msg' => "Successfully logged in!"));
	} else {
		echo json_encode(array('err' => -1, 'msg' => "Username/Password wrong!"));
	}
	return;
}

/*Check if it's a register request*/
if ($_POST["action"] == "register") {
	/*Create a random salt key*/
	$random_salt = hash('sha512', uniqid(mt_rand(1, mt_getrandmax()), true));
	/*Hash the password with the random salt*/
	$password = hash('sha512', $_POST['p'] . $random_salt);

	if (!($insert_stmt = $conn->prepare("INSERT INTO user (email, password, salt) VALUES (?, ?, ?)"))) {
		echo json_encode(array('err' => -1, 'msg' => "Failed to perform registration!"));
		return;
	}
	/*Perform the query binding params*/
	$insert_stmt->bind_param('sss', $_POST['email'], $password, $random_salt);
	$insert_stmt->execute();
	$insert_stmt->store_result();
	/*Check if the query went well or not*/
	if ($insert_stmt->affected_rows <= 0) {
		if (mysqli_errno($conn) == 1062) {
			echo json_encode(array('err' => -1, 'msg' => "User already registered!"));
		} else {
			echo json_encode(array('err' => -1, 'msg' => "Error registering user!"));
		}
	} else {
		echo json_encode(array('err' => 0, 'msg' => "Successfully registered!"));
	}
	return;
}

/*If it reaches this code means that action is set, but all the other parameters were not complete*/
echo json_encode(array('err' => -1, 'msg' => "Wrong action requested!"));
return;

?>
