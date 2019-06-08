<?php

function sec_session_start() {
//Set a session name
	$session_name = 'sec_session_id';
//True if you want to ENFORCE https
	$secure = true;
//True if you want to avoid JS to access the session ID
	$httponly = true;
//Force session only to use cookies
	ini_set('session.use_only_cookies', 1);
//Read current cookie params
	$cookieParams = session_get_cookie_params();
//Set cookie params
	session_set_cookie_params($cookieParams["lifetime"], $cookieParams["path"], $cookieParams["domain"], $secure, $httponly);
//Set session name to the chosen one
	session_name($session_name);
	session_start();
//Regenerate session id for security
	session_regenerate_id();
}

function login($email, $password, $conn) {
	if ($stmt = $conn->prepare("SELECT email, password, salt FROM user WHERE email = ? LIMIT 1")) {
		$stmt->bind_param('s', $email);
		$stmt->execute();
		$stmt->store_result();
		$stmt->bind_result($username, $db_password, $salt);
		$stmt->fetch();
//Salt the inserted password
		$password = hash('sha512', $password . $salt);
//Check if the user exists
		if ($stmt->num_rows == 1) {
//Check for the hashed password to match the stored one
			if ($db_password == $password) {
//Correct password, set all session parameters
				$_SESSION['username'] = $username;
				$_SESSION['timestamp'] = time();
				$_SESSION['login_string'] = hash('sha512', $password . $_SERVER['HTTP_USER_AGENT']);
				return SuccessObject::LOGIN;
			} else {
				return ErrorObject::PASSWORD_WRONG;
			}
		} else {
			return ErrorObject::MISSING_RECORD;
		}
	} else {
		return ErrorObject::DB_INTERNAL_ERROR;
	}
}

function login_check($conn) {
//Check that all session variables are correctly set
	if (isset($_SESSION['username'], $_SESSION['login_string'], $_SESSION['timestamp'])) {
		if (time() - $_SESSION['timestamp'] < 120) {
			if ($stmt = $conn->prepare("SELECT password FROM user WHERE email = ? LIMIT 1")) {
				$stmt->bind_param('s', $_SESSION['username']);
				$stmt->execute();
				$stmt->store_result();
				if ($stmt->num_rows == 1) {
//If the user exists control if it's still the same
					$stmt->bind_result($password);
					$stmt->fetch();
					$login_check = hash('sha512', $password . $_SERVER['HTTP_USER_AGENT']);
					if ($login_check == $_SESSION['login_string']) {
						return true;
					} else {
						return ErrorObject::LOGIN_CHECK_FAIL;
					}
				} else {
					return ErrorObject::MISSING_RECORD;
				}
			} else {
				return ErrorObject::DB_INTERNAL_ERROR;
			}
		} else {
			logout();
			return ErrorObject::EXPIRED_SESSION;
		}
	} else {
		return ErrorObject::MISSING_DATA;
	}
}

function logout() {
///Take the session parameters
	$params = session_get_cookie_params();
//Erase all session values
	$_SESSION = array();
//Set the session type cookie
	setcookie(session_name(), '', time() - 42000, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
//Finally destroy the session
	session_destroy();
	return SuccessObject::LOGOUT;
}

function register($email, $password, $conn) {
//Create a random salt key
	$random_salt = hash('sha512', uniqid(mt_rand(1, mt_getrandmax()), true));
//Hash the password with the random salt
	$password = hash('sha512', $password . $random_salt);
	if ($insert_stmt = $conn->prepare("INSERT INTO user (email, password, salt) VALUES (?, ?, ?)")) {
//Perform the query binding params
		$insert_stmt->bind_param('sss', $email, $password, $random_salt);
		$insert_stmt->execute();
		$insert_stmt->store_result();
//Check if the query went well or not
		if ($insert_stmt->affected_rows <= 0) {
			if (mysqli_errno($conn) == 1062) {
				return ErrorObject::RECORD_DUPLICATE;
			} else {
				return ErrorObject::DB_INTERNAL_ERROR;
			}
		} else {
			return SuccessObject::REGISTERED;
		}
	} else {
		return ErrorObject::DB_INTERNAL_ERROR;
	}
}

function purchaseSeat($email, $conn) {
	if ($insert_stmt = $conn->prepare("UPDATE reservation SET purchased = 1 WHERE email = ?")) {
		$insert_stmt->bind_param('s', $_SESSION['username']);
		$insert_stmt->execute();
		$insert_stmt->store_result();
		if ($insert_stmt->affected_rows <= 0) {
			return ErrorObject::SEAT_NOT_PRESENT;
		} else {
			return SuccessObject::SEAT_PURCHASE;
		}
	} else {
		return ErrorObject::DB_INTERNAL_ERROR;
	}
}

function reserveSeat($username, $seat, $conn) {
	$alreadyPresent = false;
	/*Check if thicket already bought, or delete old reservation*/
	if ($stm = $conn->prepare("SELECT email, purchased FROM reservation WHERE seat = ? LIMIT 1 FOR UPDATE")) {
		$stm->bind_param("s", $seat);
		$stm->execute();
		$stm->bind_result($email, $ispurchased);
		$stm->fetch();
		if (!is_null($ispurchased)) {
			if (!$ispurchased) {
				$alreadyPresent = true;
				$isunreserved = $username == $email ? true : false;
				$stm->fetch();
				if ($isunreserved) {
					if ($stmd = $conn->prepare("DELETE FROM reservation WHERE seat = ?")) {
						$stmd->bind_param("s", $seat);
						$stmd->execute();
						$stmd->store_result();
						if ($stmd->affected_rows <= 0) {
							return ErrorObject::DB_INTERNAL_ERROR;
						} else {
							return SuccessObject::SEAT_UNRESERVED;
						}
					} else {
						return ErrorObject::DB_INTERNAL_ERROR;
					}
				}
			} else {
				return ErrorObject::SEAT_ALREADY_SOLD;
			}
		}
	} else {
		return ErrorObject::DB_INTERNAL_ERROR;
	}

	$statement = $alreadyPresent ? "UPDATE reservation SET email = ? WHERE seat = ?" : "INSERT INTO reservation (email, seat, purchased) VALUES (?, ?, 0)";
	/*Proceed reserving the seat*/
	if ($insert_stmt = $conn->prepare($statement)) {
		$insert_stmt->bind_param('ss', $_SESSION['username'], $_POST["id"]);
		$insert_stmt->execute();
		$insert_stmt->store_result();
		if ($insert_stmt->affected_rows <= 0) {
			if (mysqli_errno($conn) == 1062) {
				return ErrorObject::SEAT_ALREADY_SOLD;
			} else {
				return ErrorObject::DB_INTERNAL_ERROR;
			}
		} else {
			return SuccessObject::SEAT_RESERVED;
		}
	} else {
		return ErrorObject::DB_INTERNAL_ERROR;
	}
}

abstract class ErrorObject {
	const MISSING_DATA = array('err' => -1, 'msg' => "Missing parameters in the request.");
	const EXPIRED_SESSION = array('err' => -1, 'msg' => "Your session has expired, please login again.");
	const MISSING_RECORD = array('err' => -1, 'msg' => "It does not seem to exist, please retry.");
	const LOGIN_CHECK_FAIL = array('err' => -1, 'msg' => "Something has appened in your login session.");
	const PASSWORD_WRONG = array('err' => -1, 'msg' => "Password wrong, please try again.");
	const PASSWORD_NOT_COMPLIANT = array('err' => -1, 'msg' => "Password not compliant.");
	const EMAIL_NOT_COMPLIANT = array('err' => -1, 'msg' => "Email not compliant.");
	const DB_INTERNAL_ERROR = array('err' => -1, 'msg' => "We experienced an internal error, please try again.");
	const RECORD_DUPLICATE = array('err' => -1, 'msg' => "It seems already to exists.");
	const CODE_INJECTION = array('err' => -1, 'msg' => "It seems that you tried to inject some code.");
	const HTTPS_ENFORCE = array('err' => -1, 'msg' => "To perform that operation you must access through HTTPS.");
	const SEAT_OUT_DOMAIN = array('err' => -1, 'msg' => "The requested seat seems not to exist.");
	const SEAT_NOT_PRESENT = array('err' => -1, 'msg' => "To perform that action you need to reserve at least 1 seat.");
	const SEAT_ALREADY_SOLD = array('err' => -1, 'msg' => "The seat has already been purchased.");
}

abstract class SuccessObject {
	const LOGIN = array('err' => 0, 'msg' => "Successfully logged.");
	const LOGOUT = array('err' => 0, 'msg' => "Successfully logged.");
	const REGISTERED = array('err' => 0, 'msg' => "Successfully Registered, now you can login.");
	const SEAT_PURCHASE = array('err' => 0, 'msg' => "Purchase successfully completed.");
	const SEAT_RESERVED = array('err' => 0, 'msg' => "Seat successfully reserved.");
	const SEAT_UNRESERVED = array('err' => 1, 'msg' => "Seat successfully unreserved.");
}

?>
