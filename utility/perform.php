<?php

require_once "db.php";
require_once "utility.php";
require_once "airconf.php";

/*Start a new secure session if not present*/
if (session_status() == PHP_SESSION_NONE) {
	sec_session_start();
}

/*Check if the user is logged*/
if (!login_check($conn)) {
	if (isset($_SESSION['username'])) {
		logout();
		echo json_encode(array('err' => -100, 'msg' => "Your session has expired, pleare login again!"));
	} else {
		echo json_encode(array('err' => -1, 'msg' => "Login required to perform that action!"));
	}
	return;
}

$_SESSION['timestamp'] = time();

/*Check if the action is reserve and there's the id of the seat*/
if (isset($_POST["action"], $_POST["id"]) && $_POST["action"] == "reserve") {
	/*Check if the seat is correct in the domain*/
	$maxseat = chr(ord('A') + $length - 1) . strval($width);
	if ($_POST["id"] < 'A0' || $_POST["id"] > $maxseat) {
		echo json_encode(array('err' => -1, 'msg' => "Bad seat id!"));
		return;
	}

	$alreadyPresent = false;
	/*Check if thicket already bought, or delete old reservation*/
	if ($stm = $conn->prepare("SELECT email, purchased FROM reservation WHERE seat = ? LIMIT 1 FOR UPDATE")) {
		$stm->bind_param("s", $_POST["id"]);
		$stm->execute();
		$stm->bind_result($email, $ispurchased);
		$stm->fetch();
		if (!is_null($ispurchased)) {
			if ($ispurchased) {
				echo json_encode(array('err' => -1, 'msg' => "Seat already purchased"));
				return;
			}
			$alreadyPresent = true;
			$isunreserved = $_SESSION["username"] == $email ? true : false;
			$stm->fetch();
			if ($isunreserved && $stmd = $conn->prepare("DELETE FROM reservation WHERE seat = ?")) {
				$stmd->bind_param("s", $_POST["id"]);
				$stmd->execute();
				$stmd->store_result();
				if ($stmd->affected_rows <= 0) {
					echo json_encode(array('err' => -1, 'msg' => "Error changing reservation"));
					return;
				}
				echo json_encode(array('err' => 1, 'msg' => "Seat successfully unreserved!"));
				return;
			}
		}
	}

	$statement = $alreadyPresent ? "UPDATE reservation SET email = ? WHERE seat = ?" : "INSERT INTO reservation (email, seat, purchased) VALUES (?, ?, 0)";
	/*Proceed reserving the seat*/
	if ($insert_stmt = $conn->prepare($statement)) {
		$insert_stmt->bind_param('ss', $_SESSION['username'], $_POST["id"]);
		$insert_stmt->execute();
		$insert_stmt->store_result();
		if ($insert_stmt->affected_rows <= 0) {
			if (mysqli_errno($conn) == 1062) {
				echo json_encode(array('err' => -1, 'msg' => "Error duplicate reservation for that seat"));
			} else {
				echo json_encode(array('err' => -1, 'msg' => "Error inserting reservation"));
			}
		} else {
			echo json_encode(array('err' => 0, 'msg' => "Seat successfully reserved!"));
		}
		return;
	}
}

/*Check if the action is BUY all the user current reserved tickets*/
if (isset($_POST["action"]) && $_POST["action"] == "buy") {
	if ($insert_stmt = $conn->prepare("UPDATE reservation SET purchased = 1 WHERE email = ?")) {
		$insert_stmt->bind_param('s', $_SESSION['username']);
		$insert_stmt->execute();
		$insert_stmt->store_result();
		if ($insert_stmt->affected_rows <= 0) {
			echo json_encode(array('err' => -1, 'msg' => "To perform that action you need to reserve at least 1 seat!"));
		} else if ($insert_stmt->affected_rows > 1) {
			echo json_encode(array('err' => 0, 'msg' => "Seats successfully purchased!"));
		} else {
			echo json_encode(array('err' => 0, 'msg' => "Seat successfully purchased!"));
		}
		return;
	}
}

/*If it reaches this point means that some params is missing or bad action*/
echo json_encode(array('err' => -1, 'msg' => 'Wrong data sent in the request'));
return;

?>