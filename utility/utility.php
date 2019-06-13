<?php

function sec_session_start() {
//Force session only to use cookies
	ini_set('session.use_only_cookies', 1);
//Read current cookie params
	$cookieParams = session_get_cookie_params();
//Set cookie params
	session_set_cookie_params($cookieParams["lifetime"], $cookieParams["path"], $cookieParams["domain"], true, true);
//Set session name to the chosen one
	session_name('sec_session_id');
	session_start();
//Regenerate session id for security
	session_regenerate_id();
}

function login($email, $password, $conn, $remember) {
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
	$myReserved = retrieveUserReserved($email, $conn);
	if (sizeof($myReserved) == sizeof($_SESSION['myreserved'])) {
		if ($insert_stmt = $conn->prepare("UPDATE reservation SET purchased = 1 WHERE email = ?")) {
			$insert_stmt->bind_param('s', $email);
			$insert_stmt->execute();
			$insert_stmt->store_result();
			if ($insert_stmt->affected_rows <= 0) {
				return ErrorObject::SEAT_NOT_PRESENT;
			} else {
				$_SESSION['myreserved'] = [];
				return SuccessObject::SEAT_PURCHASE;
			}
		} else {
			return ErrorObject::DB_INTERNAL_ERROR;
		}
	} else {
		$_SESSION['myreserved'] = [];
		if ($insert_stmt = $conn->prepare("DELETE FROM reservation WHERE email = ? AND purchased = 0")) {
			$insert_stmt->bind_param('s', $email);
			$insert_stmt->execute();
			return ErrorObject::SEAT_CHANGED;
		} else {
			return ErrorObject::DB_INTERNAL_ERROR;
		}
	}
}

function retrieveUserReserved($email, $conn) {
	$seats = [];
	if ($insert_stmt = $conn->prepare("SELECT seat FROM reservation WHERE email = ? AND purchased = 0")) {
		$insert_stmt->bind_param('s', $email);
		$insert_stmt->execute();
		$insert_stmt->bind_result($seat);
		while ($insert_stmt->fetch()) {
			array_push($seats, $seat);
		}

	}
	return $seats;
}

function reserveSeat($username, $seat, $conn) {
	$conn->autocommit(FALSE);
	$query;
	$isReserve = true;

	/*Retrieve seat reservation infos from db if already present*/
	if ($stm = $conn->prepare("SELECT email, purchased FROM reservation WHERE seat = ? LIMIT 1 FOR UPDATE")) {
		$stm->bind_param("s", $seat);
		$stm->execute();
		$stm->bind_result($seatEmail, $seatIsPurchased);
		while ($stm->fetch()) {
			if ($seatIsPurchased == 1) {
				return ErrorObject::SEAT_ALREADY_SOLD;
			}
		}
	} else {
		return ErrorObject::DB_INTERNAL_ERROR;
	}

	//Correctly prepare query and session variable
	if (in_array($seat, $_SESSION['myreserved'])) {
		//UNRESERVE
		$isReserve = false;
		$index = array_search($seat, $_SESSION['myreserved']);
		unset($_SESSION['myreserved'][$index]);
		if (!is_null($seatIsPurchased) && !is_null($seatEmail) && $seatEmail !== $username) {
			return SuccessObject::SEAT_RERESERVED;
		} else {
			$query = "DELETE FROM reservation WHERE email = ? AND seat = ?";
		}
	} else {
		//RESERVE
		array_push($_SESSION['myreserved'], $seat);
		$query = !is_null($seatIsPurchased) ? "UPDATE reservation SET email = ? WHERE seat = ?" : "INSERT INTO reservation VALUES (?, ?, 0)";
	}

	//Perform query
	if ($stm2 = $conn->prepare($query)) {
		$stm2->bind_param('ss', $username, $seat);
		$stm2->execute();
		$stm2->store_result();
		if ($stm2->affected_rows <= 0) {
			return ErrorObject::DB_INTERNAL_ERROR;
		} else {
			$conn->commit();
			return $isReserve ? SuccessObject::SEAT_RESERVED : SuccessObject::SEAT_UNRESERVED;
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
	const LOGIN_REQUIRED = array('err' => -1, 'msg' => "To perform that action you need to be logged.");
	const PASSWORD_WRONG = array('err' => -1, 'msg' => "Password wrong, please try again.");
	const PASSWORD_NOT_COMPLIANT = array('err' => -1, 'msg' => "Password not compliant.");
	const EMAIL_NOT_COMPLIANT = array('err' => -1, 'msg' => "Email not compliant.");
	const DB_INTERNAL_ERROR = array('err' => -1, 'msg' => "We experienced an internal error, please try again.");
	const RECORD_DUPLICATE = array('err' => -1, 'msg' => "It seems already to exists.");
	const CODE_INJECTION = array('err' => -1, 'msg' => "It seems that you tried to inject some code.");
	const HTTPS_ENFORCE = array('err' => -1, 'msg' => "To perform that operation you must access through HTTPS.");
	const SEAT_OUT_DOMAIN = array('err' => -1, 'msg' => "The requested seat seems not to exist.");
	const SEAT_NOT_PRESENT = array('err' => -2, 'msg' => "To perform that action you need to reserve at least 1 seat.");
	const SEAT_ALREADY_SOLD = array('err' => -1, 'msg' => "The seat has already been purchased.");
	const SEAT_CHANGED = array('err' => -1, 'msg' => "Purchase failed because one of your seats has been reserved in the mean while.");
}

abstract class SuccessObject {
	const LOGIN = array('err' => 0, 'msg' => "Successfully logged.");
	const LOGOUT = array('err' => 0, 'msg' => "Successfully logged.");
	const REGISTERED = array('err' => 0, 'msg' => "Successfully Registered.");
	const SEAT_PURCHASE = array('err' => 0, 'msg' => "Purchase successfully completed.");
	const SEAT_RERESERVED = array('err' => 1, 'msg' => "Seat unreserved, but reserved by someone else.");
	const SEAT_RESERVED = array('err' => 0, 'msg' => "Seat successfully reserved.");
	const SEAT_UNRESERVED = array('err' => 1, 'msg' => "Seat successfully unreserved.");
}

?>
