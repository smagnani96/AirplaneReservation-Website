<?php

function sec_session_start() {
	/*Set a session name*/
	$session_name = 'sec_session_id';
	/*True if you want to ENFORCE https*/
	$secure = true;
	/*True if you want to avoid JS to access the session ID*/
	$httponly = true;
	/*Force session only to use cookies*/
	ini_set('session.use_only_cookies', 1);
	/*Read current cookie params*/
	$cookieParams = session_get_cookie_params();
	/*Set cookie params*/
	session_set_cookie_params($cookieParams["lifetime"], $cookieParams["path"], $cookieParams["domain"], $secure, $httponly);
	/*Set session name to the chosen one*/
	session_name($session_name);
	session_start();
	/*Regenerate session id for security*/
	session_regenerate_id();
}

function checkbrute($username, $conn) {
	/*Check if too many login trials in the last 30 mins*/
	$valid_attempts = time() - (30 * 60);
	if ($stmt = $conn->prepare("SELECT time FROM log WHERE email = ? AND time > '$valid_attempts'")) {
		$stmt->bind_param('s', $username);
		$stmt->execute();
		$stmt->store_result();
		if ($stmt->num_rows > 5) {
			return true;
		}
	}
	return false;
}

function login($email, $password, $conn) {
	if ($stmt = $conn->prepare("SELECT email, password, salt FROM user WHERE email = ? LIMIT 1")) {
		$stmt->bind_param('s', $email);
		$stmt->execute();
		$stmt->store_result();
		$stmt->bind_result($username, $db_password, $salt);
		$stmt->fetch();
		/*Salt the inserted password*/
		$password = hash('sha512', $password . $salt);
		/*Check if the user exists*/
		if ($stmt->num_rows == 1) {
			/*Check if the user has too many login trials*/
			if (checkbrute($username, $conn) == true) {
				return false;
			} else {
				/*Check for the hashed password to match the stored one*/
				if ($db_password == $password) {
					/*Correct password, set all session parameters*/
					$_SESSION['username'] = $username;
					$_SESSION['timestamp'] = time();
					$_SESSION['login_string'] = hash('sha512', $password . $_SERVER['HTTP_USER_AGENT']);
					return true;
				} else {
					/*Wrong password, log it*/
					$now = time();
					$conn->query("INSERT INTO log (email, time) VALUES ('$username', '$now')");
					return false;
				}
			}
		} else {
			/*The user doesn't exist*/
			return false;
		}
	}
	return false;
}

function login_check($conn) {
	/*Check that all session variables are correctly set*/
	if (isset($_SESSION['username'], $_SESSION['login_string'], $_SESSION['timestamp'])) {
		if (time() - $_SESSION['timestamp'] < 120) {
			if ($stmt = $conn->prepare("SELECT password FROM user WHERE email = ? LIMIT 1")) {
				$stmt->bind_param('s', $_SESSION['username']);
				$stmt->execute();
				$stmt->store_result();
				if ($stmt->num_rows == 1) {
					/*If the user exists control if it's still the same*/
					$stmt->bind_result($password);
					$stmt->fetch();
					$login_check = hash('sha512', $password . $_SERVER['HTTP_USER_AGENT']);
					if ($login_check == $_SESSION['login_string']) {
						/*Successfully logged in*/
						return true;
					}
				}
			}
		}
	}
	return false;
}

function logout() {
	/*Take the session parameters*/
	$params = session_get_cookie_params();
	/*Erase all session values*/
	$_SESSION = array();
	/*Set the session type cookie*/
	setcookie(session_name(), '', time() - 42000, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
	/*Finally destroy the session*/
	session_destroy();
}

?>
