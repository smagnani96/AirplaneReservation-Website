<?php

require_once "db.php";
require_once "utility.php";
require_once "config.php";
require_once "checkerAccess.php";
require_once "checkerHttps.php";

sec_session_start();

/*Check if the user is logged*/
$ret = login_check($conn);
if ($ret !== true) {
	echo json_encode($ret);
	return;
}

/*Updating last action timestamp*/
$_SESSION['timestamp'] = time();

/*Check for correct parameters*/
if (!isset($_POST['action']) || !in_array($_POST['action'], array('buy', 'reserve'))
	|| ($_POST['action'] == 'reserve' && !isset($_POST['id']))) {
	echo json_encode(ErrorObject::MISSING_DATA);
	return;
}

/*Check for tags/code injection*/
if (filter_var($_POST['id'], FILTER_SANITIZE_STRING) != $_POST['id']) {
	echo json_encode(ErrorObject::CODE_INJECTION);
	return;
}

/*Check if the action is reserve and there's the id of the seat*/
if ($_POST["action"] == "reserve") {
	/*Check if the seat is correct in the domain*/
	$maxSeat = chr(ord('A') + AIRPLANE_LENGTH - 1) . strval(AIRPLANE_WIDTH);
	if ($_POST["id"] < 'A1' || $_POST["id"] > $maxSeat) {
		echo json_encode(ErrorObject::SEAT_OUT_DOMAIN);
	} else {
		echo json_encode(reserveSeat($_SESSION['username'], $_POST['id'], $conn));
	}
	return;
}

/*Check if the action is BUY all the user current reserved tickets*/
if ($_POST["action"] == "buy") {
	/*Check if at least 1 seat reserved*/
	if (empty($_SESSION['myReserved'])) {
		echo json_encode(ErrorObject::SEAT_NOT_PRESENT);
		return;
	} else {
		echo json_encode(buySeats($_SESSION['username'], $conn));
		return;
	}
}
